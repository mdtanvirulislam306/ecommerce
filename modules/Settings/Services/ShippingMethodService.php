<?php

namespace Modules\Settings\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Settings\Models\ShippingMethod;

class ShippingMethodService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return ShippingMethod::query()
            ->when($search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%");
                $inner->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (ShippingMethod $row) => $this->format($row));
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): ShippingMethod
    {
        return ShippingMethod::query()->create([
            'name' => $data['name'] ?? null,
            'code' => $data['code'] ?? null,
            'flat_rate' => $data['flat_rate'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(ShippingMethod $row, array $data): ShippingMethod
    {
        $row->update([
            'name' => array_key_exists('name', $data) ? $data['name'] : $row->name,
            'code' => array_key_exists('code', $data) ? $data['code'] : $row->code,
            'flat_rate' => array_key_exists('flat_rate', $data) ? $data['flat_rate'] : $row->flat_rate,
            'is_active' => array_key_exists('is_active', $data) ? $data['is_active'] : $row->is_active,
        ]);

        return $row->fresh();
    }

    public function delete(ShippingMethod $row): void
    {
        $row->delete();
    }

    /** @return array<string, mixed> */
    public function format(ShippingMethod $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'code' => $row->code,
            'flat_rate' => $row->flat_rate,
            'is_active' => $row->is_active,
        ];
    }
}
