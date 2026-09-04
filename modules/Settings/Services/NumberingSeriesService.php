<?php

namespace Modules\Settings\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Settings\Models\NumberingSeries;

class NumberingSeriesService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return NumberingSeries::query()
            ->when($search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%");
                $inner->orWhere('code', 'like', "%{$search}%");
                $inner->orWhere('prefix', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (NumberingSeries $row) => $this->format($row));
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): NumberingSeries
    {
        return NumberingSeries::query()->create([
            'name' => $data['name'] ?? null,
            'code' => $data['code'] ?? null,
            'prefix' => $data['prefix'] ?? null,
            'next_number' => $data['next_number'] ?? 1,
            'pad_length' => $data['pad_length'] ?? 5,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(NumberingSeries $row, array $data): NumberingSeries
    {
        $row->update([
            'name' => array_key_exists('name', $data) ? $data['name'] : $row->name,
            'code' => array_key_exists('code', $data) ? $data['code'] : $row->code,
            'prefix' => array_key_exists('prefix', $data) ? $data['prefix'] : $row->prefix,
            'next_number' => array_key_exists('next_number', $data) ? $data['next_number'] : $row->next_number,
            'pad_length' => array_key_exists('pad_length', $data) ? $data['pad_length'] : $row->pad_length,
            'is_active' => array_key_exists('is_active', $data) ? $data['is_active'] : $row->is_active,
        ]);

        return $row->fresh();
    }

    public function delete(NumberingSeries $row): void
    {
        $row->delete();
    }

    /** @return array<string, mixed> */
    public function format(NumberingSeries $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'code' => $row->code,
            'prefix' => $row->prefix,
            'next_number' => $row->next_number,
            'pad_length' => $row->pad_length,
            'is_active' => $row->is_active,
        ];
    }
}
