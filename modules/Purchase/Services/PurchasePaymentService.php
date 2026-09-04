<?php

namespace Modules\Purchase\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchasePayment;

class PurchasePaymentService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return PurchasePayment::query()
            ->with(['supplier:id,name,code', 'order:id,number,grand_total'])
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($supplier) => $supplier
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn ($order) => $order->where('number', 'like', "%{$search}%"));
            }))
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (PurchasePayment $payment) => $this->format($payment));
    }

    /**
     * @return list<array{id: int, number: string, supplier_name: string, grand_total: string, paid: string, balance: string}>
     */
    public function payableOrders(): array
    {
        return PurchaseOrder::query()
            ->with('supplier:id,name')
            ->whereNotIn('status', [PurchaseOrderStatus::Draft->value, PurchaseOrderStatus::Cancelled->value])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(function (PurchaseOrder $order) {
                $paid = (float) PurchasePayment::query()
                    ->where('purchase_order_id', $order->id)
                    ->sum('amount');
                $total = (float) $order->grand_total;

                return [
                    'id' => $order->id,
                    'number' => $order->number,
                    'supplier_id' => $order->supplier_id,
                    'supplier_name' => $order->supplier?->name,
                    'currency' => $order->currency,
                    'grand_total' => (string) $order->grand_total,
                    'paid' => number_format($paid, 4, '.', ''),
                    'balance' => number_format(max(0, $total - $paid), 4, '.', ''),
                ];
            })
            ->all();
    }

    /**
     * @param  array{
     *     purchase_order_id: int,
     *     amount: float|int|string,
     *     method?: string,
     *     reference?: string|null,
     *     paid_at: string,
     *     notes?: string|null
     * }  $data
     */
    public function create(array $data, ?int $userId = null): PurchasePayment
    {
        return DB::transaction(function () use ($data, $userId) {
            $order = PurchaseOrder::query()->whereKey($data['purchase_order_id'])->lockForUpdate()->firstOrFail();

            if (in_array($order->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Cancelled], true)) {
                throw ValidationException::withMessages([
                    'purchase_order_id' => 'Cannot pay against a draft or cancelled purchase order.',
                ]);
            }

            $amount = (float) $data['amount'];

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount must be greater than zero.',
                ]);
            }

            $paid = (float) PurchasePayment::query()
                ->where('purchase_order_id', $order->id)
                ->sum('amount');

            $balance = (float) $order->grand_total - $paid;

            if ($amount > $balance + 0.00005) {
                throw ValidationException::withMessages([
                    'amount' => "Payment exceeds remaining balance of {$balance}.",
                ]);
            }

            return PurchasePayment::query()->create([
                'number' => $this->nextNumber(),
                'purchase_order_id' => $order->id,
                'supplier_id' => $order->supplier_id,
                'amount' => number_format($amount, 4, '.', ''),
                'currency' => $order->currency,
                'method' => $data['method'] ?? 'cash',
                'reference' => $data['reference'] ?? null,
                'paid_at' => $data['paid_at'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);
        });
    }

    public function update(PurchasePayment $payment, array $data): PurchasePayment
    {
        $payment->update([
            'method' => $data['method'] ?? $payment->method,
            'reference' => $data['reference'] ?? $payment->reference,
            'paid_at' => $data['paid_at'] ?? $payment->paid_at,
            'notes' => $data['notes'] ?? $payment->notes,
        ]);

        return $payment->fresh(['supplier', 'order']);
    }

    public function delete(PurchasePayment $payment): void
    {
        $payment->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function format(PurchasePayment $payment): array
    {
        return [
            'id' => $payment->id,
            'number' => $payment->number,
            'purchase_order_id' => $payment->purchase_order_id,
            'order_number' => $payment->order?->number,
            'supplier_name' => $payment->supplier?->name,
            'amount' => (string) $payment->amount,
            'currency' => $payment->currency,
            'method' => $payment->method,
            'reference' => $payment->reference,
            'paid_at' => $payment->paid_at?->toDateString(),
            'notes' => $payment->notes,
        ];
    }

    private function nextNumber(): string
    {
        $seq = PurchasePayment::query()->lockForUpdate()->count() + 1;

        return 'PP-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
