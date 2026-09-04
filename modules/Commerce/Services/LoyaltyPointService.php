<?php

namespace Modules\Commerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Commerce\Models\LoyaltyPoint;

class LoyaltyPointService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return LoyaltyPoint::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, LoyaltyPoint>
     */
    public function activeAccounts(): Collection
    {
        return LoyaltyPoint::query()
            ->where('is_active', true)
            ->orderBy('customer_name')
            ->get(['id', 'customer_name', 'customer_email', 'balance']);
    }

    public function create(array $data): LoyaltyPoint
    {
        return LoyaltyPoint::query()->create([
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'] ?? null,
            'balance' => $data['balance'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(LoyaltyPoint $point, array $data): LoyaltyPoint
    {
        $point->update([
            'customer_name' => $data['customer_name'] ?? $point->customer_name,
            'customer_email' => array_key_exists('customer_email', $data) ? $data['customer_email'] : $point->customer_email,
            'is_active' => $data['is_active'] ?? $point->is_active,
        ]);

        return $point->fresh();
    }

    public function delete(LoyaltyPoint $point): void
    {
        $point->delete();
    }
}
