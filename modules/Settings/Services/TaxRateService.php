<?php

namespace Modules\Settings\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Settings\Models\TaxRate;

class TaxRateService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return TaxRate::query()
            ->when($search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%");
                $inner->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (TaxRate $row) => $this->format($row));
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): TaxRate
    {
        return TaxRate::query()->create([
            'name' => $data['name'] ?? null,
            'code' => $data['code'] ?? null,
            'rate' => $data['rate'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(TaxRate $row, array $data): TaxRate
    {
        $row->update([
            'name' => array_key_exists('name', $data) ? $data['name'] : $row->name,
            'code' => array_key_exists('code', $data) ? $data['code'] : $row->code,
            'rate' => array_key_exists('rate', $data) ? $data['rate'] : $row->rate,
            'is_active' => array_key_exists('is_active', $data) ? $data['is_active'] : $row->is_active,
        ]);

        return $row->fresh();
    }

    public function delete(TaxRate $row): void
    {
        $row->delete();
    }

    /** @return array<string, mixed> */
    public function format(TaxRate $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'code' => $row->code,
            'rate' => $row->rate,
            'is_active' => $row->is_active,
        ];
    }
}
