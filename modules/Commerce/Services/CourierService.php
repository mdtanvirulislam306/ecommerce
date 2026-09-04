<?php

namespace Modules\Commerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Commerce\Models\Courier;

class CourierService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Courier::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, Courier>
     */
    public function activeCouriers(): Collection
    {
        return Courier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    public function create(array $data): Courier
    {
        return Courier::query()->create([
            'name' => $data['name'],
            'code' => $data['code'],
            'tracking_url_template' => $data['tracking_url_template'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(Courier $courier, array $data): Courier
    {
        $courier->update([
            'name' => $data['name'] ?? $courier->name,
            'code' => $data['code'] ?? $courier->code,
            'tracking_url_template' => array_key_exists('tracking_url_template', $data) ? $data['tracking_url_template'] : $courier->tracking_url_template,
            'is_active' => $data['is_active'] ?? $courier->is_active,
        ]);

        return $courier->fresh();
    }

    public function delete(Courier $courier): void
    {
        $courier->delete();
    }
}
