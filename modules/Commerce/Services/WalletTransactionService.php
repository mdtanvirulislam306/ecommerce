<?php

namespace Modules\Commerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Commerce\Models\CustomerWallet;
use Modules\Commerce\Models\WalletTransaction;

class WalletTransactionService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return WalletTransaction::query()
            ->with('wallet:id,customer_name,customer_email')
            ->when($search, function ($query) use ($search) {
                $query->whereHas('wallet', function ($inner) use ($search) {
                    $inner->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): WalletTransaction
    {
        return DB::transaction(function () use ($data) {
            $wallet = CustomerWallet::query()->lockForUpdate()->findOrFail($data['customer_wallet_id']);
            $amount = (float) $data['amount'];
            $delta = $this->balanceDelta($data['type'], $amount);

            $transaction = WalletTransaction::query()->create([
                'customer_wallet_id' => $wallet->id,
                'type' => $data['type'],
                'amount' => $data['type'] === 'adjust' ? $amount : abs($amount),
                'note' => $data['note'] ?? null,
            ]);

            $wallet->update([
                'balance' => max(0, (float) $wallet->balance + $delta),
            ]);

            return $transaction->load('wallet:id,customer_name,customer_email');
        });
    }

    public function update(WalletTransaction $transaction, array $data): WalletTransaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            $wallet = CustomerWallet::query()->lockForUpdate()->findOrFail($transaction->customer_wallet_id);

            $oldDelta = $this->balanceDelta($transaction->type, (float) $transaction->amount);
            $newAmount = (float) $data['amount'];
            $newDelta = $this->balanceDelta($data['type'], $newAmount);

            $wallet->update([
                'balance' => max(0, (float) $wallet->balance - $oldDelta + $newDelta),
            ]);

            $transaction->update([
                'type' => $data['type'],
                'amount' => $data['type'] === 'adjust' ? $newAmount : abs($newAmount),
                'note' => array_key_exists('note', $data) ? $data['note'] : $transaction->note,
            ]);

            return $transaction->fresh()->load('wallet:id,customer_name,customer_email');
        });
    }

    public function delete(WalletTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $wallet = CustomerWallet::query()->lockForUpdate()->findOrFail($transaction->customer_wallet_id);
            $delta = $this->balanceDelta($transaction->type, (float) $transaction->amount);

            $wallet->update([
                'balance' => max(0, (float) $wallet->balance - $delta),
            ]);

            $transaction->delete();
        });
    }

    private function balanceDelta(string $type, float $amount): float
    {
        return match ($type) {
            'credit' => abs($amount),
            'debit' => -abs($amount),
            'adjust' => $amount,
            default => 0,
        };
    }
}
