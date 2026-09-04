<?php

namespace Modules\Commerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Commerce\Models\CustomerWallet;

class CustomerWalletService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return CustomerWallet::query()
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
     * @return Collection<int, CustomerWallet>
     */
    public function activeWallets(): Collection
    {
        return CustomerWallet::query()
            ->where('is_active', true)
            ->orderBy('customer_name')
            ->get(['id', 'customer_name', 'customer_email', 'balance']);
    }

    public function create(array $data): CustomerWallet
    {
        return CustomerWallet::query()->create([
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'] ?? null,
            'balance' => $data['balance'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(CustomerWallet $wallet, array $data): CustomerWallet
    {
        $wallet->update([
            'customer_name' => $data['customer_name'] ?? $wallet->customer_name,
            'customer_email' => array_key_exists('customer_email', $data) ? $data['customer_email'] : $wallet->customer_email,
            'is_active' => $data['is_active'] ?? $wallet->is_active,
        ]);

        return $wallet->fresh();
    }

    public function delete(CustomerWallet $wallet): void
    {
        $wallet->delete();
    }
}
