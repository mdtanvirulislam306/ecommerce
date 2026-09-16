<?php

namespace App\Http\Middleware;

use App\Core\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\Billing\Enums\SubscriptionStatus;
use Modules\Billing\Models\Subscription;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantActive
{
    public function __construct(private readonly TenantContext $tenants) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->tenants->isPlatformRequest()) {
            return $next($request);
        }

        $tenant = $this->tenants->get();

        if ($tenant === null) {
            return $next($request);
        }

        if ($tenant->isSuspended() || $this->subscriptionExpired($tenant->id)) {
            $message = $tenant->isSuspended()
                ? 'This shop is suspended. Contact the platform administrator.'
                : 'This shop subscription has expired. Contact the platform administrator.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            abort(403, $message);
        }

        $user = $request->user();

        if ($user && ! ($user->is_platform_admin ?? false)) {
            if ($user->tenant_id !== null && (int) $user->tenant_id !== (int) $tenant->id) {
                abort(403, 'You do not have access to this shop.');
            }
        }

        return $next($request);
    }

    private function subscriptionExpired(int $tenantId): bool
    {
        if (! Schema::hasTable('subscriptions') || ! Schema::hasColumn('subscriptions', 'tenant_id')) {
            return false;
        }

        $subscription = Subscription::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', SubscriptionStatus::Active)
            ->latest('id')
            ->first();

        if ($subscription === null) {
            return false;
        }

        return $subscription->ends_at !== null && $subscription->ends_at->isPast();
    }
}
