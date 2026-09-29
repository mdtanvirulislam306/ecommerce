<?php

namespace Modules\Settings\Services;

use App\Core\Support\Service;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Settings\Enums\StaffStatus;
use Modules\Settings\Models\Role;

class UserListService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25, ?StaffStatus $status = null, ?User $viewer = null): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return User::query()
            ->inCurrentShop()
            ->with(['roles:id,name', 'inviter:id,name'])
            ->when($search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($status, fn (Builder $q, StaffStatus $status) => match ($status) {
                StaffStatus::Deactivated => $q->whereNotNull('deactivated_at'),
                StaffStatus::Invited => $q->whereNull('deactivated_at')->whereNotNull('invited_at')->whereNull('invitation_accepted_at'),
                StaffStatus::Active => $q->whereNull('deactivated_at')->where(fn (Builder $inner) => $inner
                    ->whereNull('invited_at')
                    ->orWhereNotNull('invitation_accepted_at')),
            })
            ->orderByDesc('is_owner')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_owner' => (bool) $user->is_owner,
                'is_self' => $viewer !== null && $user->is($viewer),
                'status' => $user->staffStatus()->value,
                'status_label' => $user->staffStatus()->label(),
                'roles' => $user->roles->map(fn (Role $role) => ['id' => $role->id, 'name' => $role->name])->values()->all(),
                'invited_by' => $user->inviter?->name,
                'invited_at' => $user->invited_at?->toIso8601String(),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'deactivated_at' => $user->deactivated_at?->toIso8601String(),
                'created_at' => $user->created_at?->toDateString(),
            ]);
    }

    /**
     * @return list<array{id: int, name: string, description: string|null, permissions_count: int}>
     */
    public function roleOptions(): array
    {
        return Role::query()
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'permissions'])
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'permissions_count' => count($role->permissions ?? []),
            ])
            ->all();
    }

    /**
     * @param  list<int>  $roleIds  ids already validated to belong to the current shop
     */
    public function syncRoles(User $user, array $roleIds): void
    {
        $user->roles()->sync($roleIds);
    }
}
