<?php

namespace Modules\Catalog\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Unit;
use Modules\Catalog\Models\UnitConversion;

class UnitService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Unit::query()
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Unit
    {
        return DB::transaction(function () use ($data) {
            return Unit::query()->create([
                'name' => $data['name'],
                'code' => $data['code'],
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
        });
    }

    public function update(Unit $unit, array $data): Unit
    {
        return DB::transaction(function () use ($unit, $data) {
            $unit->update([
                'name' => $data['name'],
                'code' => $data['code'],
                'is_active' => $data['is_active'] ?? $unit->is_active,
                'sort_order' => $data['sort_order'] ?? $unit->sort_order,
            ]);

            return $unit->fresh();
        });
    }

    public function delete(Unit $unit): void
    {
        DB::transaction(fn () => $unit->delete());
    }

    public function upsertConversion(array $data): UnitConversion
    {
        return DB::transaction(function () use ($data) {
            return UnitConversion::query()->updateOrCreate(
                [
                    'from_unit_id' => $data['from_unit_id'],
                    'to_unit_id' => $data['to_unit_id'],
                ],
                ['factor' => $data['factor']],
            );
        });
    }

    public function deleteConversion(UnitConversion $conversion): void
    {
        DB::transaction(fn () => $conversion->delete());
    }

    public function listConversionsPaginated(int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return UnitConversion::query()
            ->with(['fromUnit:id,name,code', 'toUnit:id,name,code'])
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }
}
