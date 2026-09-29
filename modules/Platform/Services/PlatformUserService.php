<?php

namespace Modules\Platform\Services;

use App\Core\Support\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Settings\Enums\StaffStatus;

class PlatformUserService extends Service
{
    public const TYPE_OWNER = 'owner';

    public const TYPE_STAFF = 'staff';

    public const TYPE_PLATFORM_ADMIN = 'platform_admin';

    /**
     * @param  array{search?: string|null, tenant_id?: int|null, type?: string|null, status?: string|null}  $filters
     */
    public function listPaginated(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return User::query()
            ->with([
                'tenant:id,name,status',
                'roles' => fn ($query) => $query->withoutGlobalScopes()->select('roles.id', 'roles.name'),
            ])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(
                fn (Builder $inner) => $inner->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"),
            ))
            ->when($filters['tenant_id'] ?? null, fn (Builder $query, int $tenantId) => $query->where('tenant_id', $tenantId))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $this->applyType($query, $type))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $this->applyStatus($query, StaffStatus::from($status)))
            ->orderByDesc('is_platform_admin')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (User $user) => $this->format($user));
    }

    /**
     * @return array{total: int, owners: int, staff: int, platform_admins: int}
     */
    public function summary(): array
    {
        return [
            'total' => User::query()->count(),
            'owners' => $this->applyType(User::query(), self::TYPE_OWNER)->count(),
            'staff' => $this->applyType(User::query(), self::TYPE_STAFF)->count(),
            'platform_admins' => $this->applyType(User::query(), self::TYPE_PLATFORM_ADMIN)->count(),
        ];
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function shopOptions(): array
    {
        return Tenant::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn (Tenant $tenant) => ['id' => $tenant->id, 'name' => $tenant->name])
            ->all();
    }

    private function applyType(Builder $query, string $type): Builder
    {
        return match ($type) {
            self::TYPE_PLATFORM_ADMIN => $query->where('is_platform_admin', true),
            self::TYPE_OWNER => $query->where('is_platform_admin', false)->where('is_owner', true),
            default => $query->where('is_platform_admin', false)->where('is_owner', false),
        };
    }

    private function applyStatus(Builder $query, StaffStatus $status): Builder
    {
        return match ($status) {
            StaffStatus::Deactivated => $query->whereNotNull('deactivated_at'),
            StaffStatus::Invited => $query->whereNull('deactivated_at')->whereNotNull('invited_at')->whereNull('invitation_accepted_at'),
            StaffStatus::Active => $query->whereNull('deactivated_at')->where(
                fn (Builder $inner) => $inner->whereNull('invited_at')->orWhereNotNull('invitation_accepted_at'),
            ),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function format(User $user): array
    {
        $type = match (true) {
            $user->isPlatformAdmin() => self::TYPE_PLATFORM_ADMIN,
            (bool) $user->is_owner => self::TYPE_OWNER,
            default => self::TYPE_STAFF,
        };

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'type' => $type,
            'shop' => $user->tenant ? [
                'id' => $user->tenant->id,
                'name' => $user->tenant->name,
                'status' => $user->tenant->status,
            ] : null,
            'status' => $user->staffStatus()->value,
            'status_label' => $user->staffStatus()->label(),
            'roles' => $user->roles->pluck('name')->all(),
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
            'can_impersonate' => $type !== self::TYPE_PLATFORM_ADMIN
                && $user->tenant !== null
                && ! $user->isDeactivated()
                && ! $user->hasPendingInvitation(),
        ];
    }
}
