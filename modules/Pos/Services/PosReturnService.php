<?php

namespace Modules\Pos\Services;

use App\Core\Contracts\StockAvailability;
use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pos\Enums\PosOrderStatus;
use Modules\Pos\Models\PosOrder;
use Modules\Pos\Models\PosOrderItem;
use Modules\Pos\Models\PosReturn;
use Modules\Pos\Models\PosReturnItem;

class PosReturnService extends Service
{
    public function __construct(
        private readonly StockAvailability $stock,
    ) {}

    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return PosReturn::query()
            ->with(['order:id,number,customer_name'])
            ->withCount('items')
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('order', fn ($order) => $order
                        ->where('number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%"));
            }))
            ->orderByDesc('returned_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (PosReturn $return) => [
                'id' => $return->id,
                'number' => $return->number,
                'order_number' => $return->order?->number,
                'customer_name' => $return->order?->customer_name,
                'grand_total' => (string) $return->grand_total,
                'currency' => $return->currency,
                'reason' => $return->reason,
                'items_count' => $return->items_count,
                'returned_at' => $return->returned_at?->toIso8601String(),
            ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function returnableOrders(): array
    {
        return PosOrder::query()
            ->with('items')
            ->where('status', PosOrderStatus::Completed)
            ->orderByDesc('completed_at')
            ->limit(100)
            ->get()
            ->map(fn (PosOrder $order) => [
                'id' => $order->id,
                'number' => $order->number,
                'customer_name' => $order->customer_name,
                'warehouse_id' => $order->warehouse_id,
                'pos_session_id' => $order->pos_session_id,
                'currency' => $order->currency,
                'items' => $order->items->map(fn (PosOrderItem $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => (string) $item->quantity,
                    'unit_price' => (string) $item->unit_price,
                    'already_returned' => number_format(
                        (float) PosReturnItem::query()->where('pos_order_item_id', $item->id)->sum('quantity'),
                        4,
                        '.',
                        '',
                    ),
                ])->all(),
            ])
            ->all();
    }

    /**
     * @param  array{
     *     pos_order_id: int,
     *     reason?: string|null,
     *     notes?: string|null,
     *     items: list<array{pos_order_item_id: int, quantity: float|int|string}>
     * }  $data
     */
    public function create(array $data, ?int $userId = null): PosReturn
    {
        return DB::transaction(function () use ($data, $userId) {
            $order = PosOrder::query()->whereKey($data['pos_order_id'])->lockForUpdate()->with('items')->firstOrFail();

            if ($order->status !== PosOrderStatus::Completed) {
                throw ValidationException::withMessages([
                    'pos_order_id' => 'Only completed POS orders can be returned.',
                ]);
            }

            $lines = [];
            $subtotal = 0.0;

            foreach ($data['items'] as $index => $row) {
                $item = $order->items->firstWhere('id', (int) $row['pos_order_item_id']);

                if ($item === null) {
                    throw ValidationException::withMessages([
                        "items.{$index}.pos_order_item_id" => 'Invalid order line.',
                    ]);
                }

                $qty = (float) $row['quantity'];

                if ($qty <= 0) {
                    continue;
                }

                $already = (float) PosReturnItem::query()->where('pos_order_item_id', $item->id)->sum('quantity');
                $returnable = (float) $item->quantity - $already;

                if ($qty > $returnable + 0.00005) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => "Cannot return more than {$returnable} for {$item->name}.",
                    ]);
                }

                if (! $item->product_id) {
                    throw ValidationException::withMessages([
                        "items.{$index}.pos_order_item_id" => "Line {$item->name} is missing product reference.",
                    ]);
                }

                $lineTotal = $qty * (float) $item->unit_price;
                $subtotal += $lineTotal;

                $lines[] = [
                    'pos_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'quantity' => number_format($qty, 4, '.', ''),
                    'unit_price' => (string) $item->unit_price,
                    'line_total' => number_format($lineTotal, 4, '.', ''),
                    'currency' => $item->currency ?? $order->currency,
                ];
            }

            if ($lines === []) {
                throw ValidationException::withMessages([
                    'items' => 'Enter a return quantity for at least one line.',
                ]);
            }

            $return = PosReturn::query()->create([
                'number' => $this->nextNumber(),
                'status' => 'completed',
                'pos_order_id' => $order->id,
                'pos_session_id' => $order->pos_session_id,
                'warehouse_id' => $order->warehouse_id,
                'currency' => $order->currency,
                'subtotal' => number_format($subtotal, 4, '.', ''),
                'grand_total' => number_format($subtotal, 4, '.', ''),
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'returned_at' => now(),
                'created_by' => $userId,
            ]);

            foreach ($lines as $index => $line) {
                $return->items()->create([
                    ...$line,
                    'sort_order' => $index,
                ]);

                $this->stock->restock(
                    productId: $line['product_id'],
                    quantity: (float) $line['quantity'],
                    productVariantId: $line['product_variant_id'],
                    warehouseId: $order->warehouse_id,
                    referenceType: PosReturn::class,
                    referenceId: $return->id,
                    userId: $userId,
                );
            }

            return $return->fresh(['items', 'order']);
        });
    }

    private function nextNumber(): string
    {
        $seq = PosReturn::query()->lockForUpdate()->count() + 1;

        return 'POSR-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
