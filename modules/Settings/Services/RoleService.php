<?php

namespace Modules\Settings\Services;

use App\Core\Module\ModuleManager;
use App\Core\Permission\PermissionRegistry;
use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Settings\Models\Role;

class RoleService extends Service
{
    public function __construct(
        private readonly PermissionRegistry $permissions,
        private readonly ModuleManager $modules,
    ) {}

    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Role::query()
            ->withCount('users')
            ->when($search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%");
                $inner->orWhere('slug', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Role $row) => $this->format($row));
    }

    /**
     * Only modules in the shop's plan are offered, so a role can never grant more than the plan includes.
     *
     * @return array{permission_catalog: list<array<string, mixed>>}
     */
    public function formOptions(): array
    {
        return [
            'permission_catalog' => $this->permissions->catalog($this->modules->enabledCodes()),
        ];
    }

    /**
     * @return list<string>
     */
    public function assignablePermissions(): array
    {
        return $this->permissions->allKeys($this->modules->enabledCodes());
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): Role
    {
        return Role::query()->create([
            'name' => $data['name'] ?? null,
            'slug' => $data['slug'] ?? null,
            'description' => $data['description'] ?? null,
            'permissions' => array_values($data['permissions'] ?? []),
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(Role $row, array $data): Role
    {
        $row->update([
            'name' => array_key_exists('name', $data) ? $data['name'] : $row->name,
            'slug' => array_key_exists('slug', $data) ? $data['slug'] : $row->slug,
            'description' => array_key_exists('description', $data) ? $data['description'] : $row->description,
            'permissions' => array_key_exists('permissions', $data) ? array_values($data['permissions']) : $row->permissions,
        ]);

        return $row->fresh();
    }

    public function delete(Role $row): void
    {
        $row->delete();
    }

    /** @return array<string, mixed> */
    public function format(Role $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'slug' => $row->slug,
            'description' => $row->description,
            'permissions' => $row->permissions ?? [],
            'users_count' => $row->users_count,
        ];
    }
}
