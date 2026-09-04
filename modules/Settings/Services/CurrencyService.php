<?php

namespace Modules\Settings\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Settings\Models\Currency;

class CurrencyService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Currency::query()
            ->when($search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%");
                $inner->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Currency $row) => $this->format($row));
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): Currency
    {
        return Currency::query()->create([
            'name' => $data['name'] ?? null,
            'code' => $data['code'] ?? null,
            'symbol' => $data['symbol'] ?? null,
            'exchange_rate' => $data['exchange_rate'] ?? 1,
            'is_default' => $data['is_default'] ?? false,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(Currency $row, array $data): Currency
    {
        $row->update([
            'name' => array_key_exists('name', $data) ? $data['name'] : $row->name,
            'code' => array_key_exists('code', $data) ? $data['code'] : $row->code,
            'symbol' => array_key_exists('symbol', $data) ? $data['symbol'] : $row->symbol,
            'exchange_rate' => array_key_exists('exchange_rate', $data) ? $data['exchange_rate'] : $row->exchange_rate,
            'is_default' => array_key_exists('is_default', $data) ? $data['is_default'] : $row->is_default,
            'is_active' => array_key_exists('is_active', $data) ? $data['is_active'] : $row->is_active,
        ]);

        return $row->fresh();
    }

    public function delete(Currency $row): void
    {
        $row->delete();
    }

    /** @return array<string, mixed> */
    public function format(Currency $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'code' => $row->code,
            'symbol' => $row->symbol,
            'exchange_rate' => $row->exchange_rate,
            'is_default' => $row->is_default,
            'is_active' => $row->is_active,
        ];
    }
}
