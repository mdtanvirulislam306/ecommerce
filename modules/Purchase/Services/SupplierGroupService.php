<?php

namespace Modules\Purchase\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Modules\Purchase\Models\SupplierGroup;

class SupplierGroupService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return SupplierGroup::query()
            ->withCount('suppliers')
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return list<array{id: int, name: string, code: string}>
     */
    public function options(): array
    {
        return SupplierGroup::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (SupplierGroup $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'code' => $group->code,
            ])
            ->all();
    }

    public function create(array $data): SupplierGroup
    {
        return SupplierGroup::query()->create([
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function update(SupplierGroup $group, array $data): SupplierGroup
    {
        $group->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? $group->description,
            'is_active' => $data['is_active'] ?? $group->is_active,
            'sort_order' => $data['sort_order'] ?? $group->sort_order,
        ]);

        return $group->fresh();
    }

    public function delete(SupplierGroup $group): void
    {
        if ($group->suppliers()->exists()) {
            throw ValidationException::withMessages([
                'group' => 'Cannot delete a group that still has suppliers.',
            ]);
        }

        $group->delete();
    }
}
