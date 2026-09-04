<?php

namespace Modules\Settings\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Settings\Models\PaymentMethod;

class PaymentMethodService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return PaymentMethod::query()
            ->when($search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%");
                $inner->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (PaymentMethod $row) => $this->format($row));
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): PaymentMethod
    {
        return PaymentMethod::query()->create([
            'name' => $data['name'] ?? null,
            'code' => $data['code'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'config' => is_string($data['config'] ?? null) ? json_decode($data['config'], true) : ($data['config'] ?? []),
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(PaymentMethod $row, array $data): PaymentMethod
    {
        $row->update([
            'name' => array_key_exists('name', $data) ? $data['name'] : $row->name,
            'code' => array_key_exists('code', $data) ? $data['code'] : $row->code,
            'is_active' => array_key_exists('is_active', $data) ? $data['is_active'] : $row->is_active,
            'config' => array_key_exists('config', $data) ? $data['config'] : $row->config,
        ]);

        return $row->fresh();
    }

    public function delete(PaymentMethod $row): void
    {
        $row->delete();
    }

    /** @return array<string, mixed> */
    public function format(PaymentMethod $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'code' => $row->code,
            'is_active' => $row->is_active,
            'config' => $row->config,
            'config_json' => json_encode($row->config ?? []),
        ];
    }
}
