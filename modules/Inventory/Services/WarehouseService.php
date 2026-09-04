<?php

namespace Modules\Inventory\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Models\Warehouse;

class WarehouseService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Warehouse::query()
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
     * @return list<array{id: int, name: string, code: string, is_default: bool}>
     */
    public function options(): array
    {
        return Warehouse::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_default'])
            ->map(fn (Warehouse $warehouse) => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'code' => $warehouse->code,
                'is_default' => $warehouse->is_default,
            ])
            ->all();
    }

    public function create(array $data): Warehouse
    {
        return DB::transaction(function () use ($data) {
            if ($data['is_default'] ?? false) {
                Warehouse::query()->update(['is_default' => false]);
            }

            return Warehouse::query()->create([
                'name' => $data['name'],
                'code' => $data['code'],
                'address' => $data['address'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_default' => $data['is_default'] ?? false,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
        });
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        return DB::transaction(function () use ($warehouse, $data) {
            if ($data['is_default'] ?? false) {
                Warehouse::query()->where('id', '!=', $warehouse->id)->update(['is_default' => false]);
            }

            $warehouse->update([
                'name' => $data['name'],
                'code' => $data['code'],
                'address' => $data['address'] ?? $warehouse->address,
                'is_active' => $data['is_active'] ?? $warehouse->is_active,
                'is_default' => $data['is_default'] ?? $warehouse->is_default,
                'sort_order' => $data['sort_order'] ?? $warehouse->sort_order,
            ]);

            return $warehouse->fresh();
        });
    }

    public function delete(Warehouse $warehouse): void
    {
        if ($warehouse->stockLevels()->where('on_hand', '>', 0)->exists()) {
            throw ValidationException::withMessages([
                'warehouse' => 'Cannot delete a warehouse that still has on-hand stock.',
            ]);
        }

        DB::transaction(fn () => $warehouse->delete());
    }

    public function defaultWarehouse(): ?Warehouse
    {
        return Warehouse::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->first()
            ?? Warehouse::query()->where('is_active', true)->orderBy('sort_order')->first();
    }
}
