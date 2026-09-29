<?php

namespace Modules\Platform\Services;

use App\Core\Support\Service;
use App\Core\Tenant\TenantContext;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Settings\Models\AuditLog;

/**
 * Lets a platform admin act as a shop user. Shops live on their own domains and session cookies don't
 * cross domains, so the platform side issues a single-use, short-lived handoff token that the shop's
 * host redeems to start the session there.
 */
class ImpersonationService extends Service
{
    public const SESSION_KEY = 'impersonation';

    public const HANDOFF_TTL_SECONDS = 60;

    public const ACTION_STARTED = 'impersonation.started';

    public const ACTION_ENDED = 'impersonation.ended';

    private const CACHE_PREFIX = 'impersonation-handoff:';

    public function __construct(private readonly TenantContext $tenants) {}

    /**
     * @return string URL on the shop's own host that completes the sign-in
     */
    public function start(User $admin, Tenant $tenant, User $target, Request $request): string
    {
        $this->ensureCanImpersonate($admin, $tenant, $target);

        $token = Str::random(64);

        Cache::put(self::CACHE_PREFIX.$token, [
            'impersonator_id' => $admin->id,
            'user_id' => $target->id,
            'tenant_id' => $tenant->id,
        ], self::HANDOFF_TTL_SECONDS);

        $this->audit(self::ACTION_STARTED, $tenant->id, $admin, $target, $request);

        return $this->handoffUrl($tenant, $token, $request);
    }

    public function redeem(string $token, Request $request): User
    {
        $payload = Cache::pull(self::CACHE_PREFIX.$token);

        abort_if($payload === null, 403, 'This sign-in link has expired. Start again from the platform console.');
        abort_unless($this->tenants->id() === $payload['tenant_id'], 403, 'This sign-in link belongs to a different shop.');

        $admin = User::query()->where('is_platform_admin', true)->find($payload['impersonator_id']);
        $target = User::query()->where('tenant_id', $payload['tenant_id'])->find($payload['user_id']);

        abort_if($admin === null || $target === null, 403, 'This sign-in link is no longer valid.');

        $blocker = $this->blocker($admin, $this->tenants->get(), $target);
        abort_if($blocker !== null, 403, (string) $blocker);

        Auth::guard('web')->login($target);
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, [
            'impersonator_id' => $admin->id,
            'impersonator_name' => $admin->name,
            'tenant_id' => $payload['tenant_id'],
            'started_at' => now()->toIso8601String(),
        ]);

        return $target;
    }

    /**
     * Ends the session as the shop user and signs the platform admin back in on this host.
     *
     * @return int|null the shop that was being viewed, or null when no impersonation was running
     */
    public function leave(Request $request): ?int
    {
        $session = $request->session()->get(self::SESSION_KEY);

        if (! is_array($session)) {
            return null;
        }

        $target = $request->user('web');
        $admin = User::query()->where('is_platform_admin', true)->find($session['impersonator_id']);

        if ($admin !== null && $target !== null) {
            $this->audit(self::ACTION_ENDED, (int) $session['tenant_id'], $admin, $target, $request, [
                'minutes' => (int) round(now()->diffInMinutes($session['started_at'], true)),
            ]);
        }

        Auth::guard('web')->logout();
        $request->session()->forget(self::SESSION_KEY);
        $request->session()->regenerate();

        if ($admin !== null) {
            Auth::guard('web')->login($admin);
        }

        return (int) $session['tenant_id'];
    }

    public function isImpersonating(Request $request): bool
    {
        return is_array($request->session()->get(self::SESSION_KEY));
    }

    /**
     * @return array{impersonator_name: string, started_at: string}|null
     */
    public function current(Request $request): ?array
    {
        if (! $request->hasSession()) {
            return null;
        }

        $session = $request->session()->get(self::SESSION_KEY);

        return is_array($session) ? [
            'impersonator_name' => (string) $session['impersonator_name'],
            'started_at' => (string) $session['started_at'],
        ] : null;
    }

    /**
     * @return list<array{id: int, action: string, admin: string|null, user: string|null, minutes: int|null, ip_address: string|null, created_at: string|null}>
     */
    public function history(Tenant $tenant, int $limit = 10): array
    {
        return AuditLog::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereIn('action', [self::ACTION_STARTED, self::ACTION_ENDED])
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'admin' => $log->properties['impersonator_name'] ?? null,
                'user' => $log->properties['user_name'] ?? null,
                'minutes' => $log->properties['minutes'] ?? null,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toIso8601String(),
            ])
            ->all();
    }

    private function ensureCanImpersonate(User $admin, Tenant $tenant, User $target): void
    {
        $reason = $this->blocker($admin, $tenant, $target);

        if ($reason !== null) {
            throw ValidationException::withMessages(['impersonation' => $reason]);
        }
    }

    private function blocker(User $admin, ?Tenant $tenant, User $target): ?string
    {
        return match (true) {
            ! $admin->isPlatformAdmin() => 'Only platform administrators can sign in as shop users.',
            $tenant === null || (int) $target->tenant_id !== $tenant->id => 'That person does not belong to this shop.',
            $target->isPlatformAdmin() => 'Platform administrators cannot be impersonated.',
            $target->isDeactivated() => 'This person is deactivated. Reactivate them first.',
            $target->hasPendingInvitation() => 'This person has not accepted their invitation yet.',
            default => null,
        };
    }

    private function handoffUrl(Tenant $tenant, string $token, Request $request): string
    {
        $path = route('impersonation.redeem', $token, false);
        $domain = $tenant->domains()->where('is_active', true)->orderByDesc('is_primary')->value('domain');

        if ($domain === null) {
            return url($path);
        }

        $port = $request->getPort();
        $portSuffix = in_array($port, [80, 443], true) ? '' : ':'.$port;

        return $request->getScheme().'://'.$domain.$portSuffix.$path;
    }

    /**
     * Written to the shop's own audit log so owners can see every time the platform signed in as their staff.
     *
     * @param  array<string, mixed>  $extra
     */
    private function audit(string $action, int $tenantId, User $admin, User $target, Request $request, array $extra = []): void
    {
        AuditLog::query()->withoutGlobalScopes()->forceCreate([
            'tenant_id' => $tenantId,
            'user_id' => $admin->id,
            'action' => $action,
            'subject_type' => 'user',
            'subject_id' => $target->id,
            'properties' => [
                'impersonator_name' => $admin->name,
                'impersonator_email' => $admin->email,
                'user_name' => $target->name,
                'user_email' => $target->email,
                ...$extra,
            ],
            'ip_address' => $request->ip(),
        ]);
    }
}
