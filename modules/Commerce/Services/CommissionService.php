<?php

namespace Modules\Commerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Commerce\Models\Commission;

class CommissionService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Commission::query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Commission
    {
        return Commission::query()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'rate' => $data['rate'],
            'is_active' => $data['is_active'] ?? true,
            'description' => $data['description'] ?? null,
        ]);
    }

    public function update(Commission $commission, array $data): Commission
    {
        $commission->update([
            'name' => $data['name'] ?? $commission->name,
            'type' => $data['type'] ?? $commission->type,
            'rate' => $data['rate'] ?? $commission->rate,
            'is_active' => $data['is_active'] ?? $commission->is_active,
            'description' => array_key_exists('description', $data) ? $data['description'] : $commission->description,
        ]);

        return $commission->fresh();
    }

    public function delete(Commission $commission): void
    {
        $commission->delete();
    }
}
