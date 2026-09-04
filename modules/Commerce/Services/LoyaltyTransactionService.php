<?php

namespace Modules\Commerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Commerce\Models\LoyaltyPoint;
use Modules\Commerce\Models\LoyaltyTransaction;

class LoyaltyTransactionService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return LoyaltyTransaction::query()
            ->with('loyaltyPoint:id,customer_name,customer_email')
            ->when($search, function ($query) use ($search) {
                $query->whereHas('loyaltyPoint', function ($inner) use ($search) {
                    $inner->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): LoyaltyTransaction
    {
        return DB::transaction(function () use ($data) {
            $point = LoyaltyPoint::query()->lockForUpdate()->findOrFail($data['loyalty_point_id']);
            $points = (int) $data['points'];
            $delta = $this->balanceDelta($data['type'], $points);

            $transaction = LoyaltyTransaction::query()->create([
                'loyalty_point_id' => $point->id,
                'type' => $data['type'],
                'points' => $data['type'] === 'adjust' ? $points : abs($points),
                'note' => $data['note'] ?? null,
            ]);

            $point->update([
                'balance' => max(0, $point->balance + $delta),
            ]);

            return $transaction->load('loyaltyPoint:id,customer_name,customer_email');
        });
    }

    public function update(LoyaltyTransaction $transaction, array $data): LoyaltyTransaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            $point = LoyaltyPoint::query()->lockForUpdate()->findOrFail($transaction->loyalty_point_id);

            $oldDelta = $this->balanceDelta($transaction->type, $transaction->points);
            $newPoints = (int) $data['points'];
            $newDelta = $this->balanceDelta($data['type'], $newPoints);

            $point->update([
                'balance' => max(0, $point->balance - $oldDelta + $newDelta),
            ]);

            $transaction->update([
                'type' => $data['type'],
                'points' => $data['type'] === 'adjust' ? $newPoints : abs($newPoints),
                'note' => array_key_exists('note', $data) ? $data['note'] : $transaction->note,
            ]);

            return $transaction->fresh()->load('loyaltyPoint:id,customer_name,customer_email');
        });
    }

    public function delete(LoyaltyTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $point = LoyaltyPoint::query()->lockForUpdate()->findOrFail($transaction->loyalty_point_id);
            $delta = $this->balanceDelta($transaction->type, $transaction->points);

            $point->update([
                'balance' => max(0, $point->balance - $delta),
            ]);

            $transaction->delete();
        });
    }

    private function balanceDelta(string $type, int $points): int
    {
        return match ($type) {
            'earn' => abs($points),
            'redeem' => -abs($points),
            'adjust' => $points,
            default => 0,
        };
    }
}
