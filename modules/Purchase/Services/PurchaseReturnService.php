<?php

namespace Modules\Purchase\Services;

use App\Core\Contracts\StockAvailability;
use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderItem;
use Modules\Purchase\Models\PurchaseReturn;
use Modules\Purchase\Models\PurchaseReturnItem;

class PurchaseReturnService extends Service
{
    public function __construct(
        private readonly StockAvailability $stock,
    ) {}

    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return PurchaseReturn::query()
            ->with(['supplier:id,name,code', 'order:id,number'])
            ->withCount('items')
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($supplier) => $supplier
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn ($order) => $order->where('number', 'like', "%{$search}%"));
            }))
            ->orderByDesc('returned_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (PurchaseReturn $return) => $this->format($return));
    }

    /**
     * @return list<array{id: int, number: string, supplier_name: string, status: string}>
     */
    public function receivableOrders(): array
    {
        return PurchaseOrder::query()
            ->with('supplier:id,name')
            ->whereIn('status', [PurchaseOrderStatus::Partial->value, PurchaseOrderStatus::Received->value])
            ->orderByDesc('received_at')
            ->limit(100)
            ->get()
            ->map(fn (PurchaseOrder $order) => [
                'id' => $order->id,
                'number' => $order->number,
                'supplier_name' => $order->supplier?->name,
                'status' => $order->status->value,
                'grand_total' => (string) $order->grand_total,
                'items' => $order->items()->get()->map(fn (PurchaseOrderItem $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity_received' => (string) $item->quantity_received,
                    'unit_cost' => (string) $item->unit_cost,
                    'already_returned' => number_format(
                        (float) PurchaseReturnItem::query()
                            ->where('purchase_order_item_id', $item->id)
                            ->sum('quantity'),
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
     *     purchase_order_id: int,
     *     reason?: string|null,
     *     notes?: string|null,
     *     items: list<array{purchase_order_item_id: int, quantity: float|int|string}>
     * }  $data
     */
    public function create(array $data, ?int $userId = null): PurchaseReturn
    {
        return DB::transaction(function () use ($data, $userId) {
            $order = PurchaseOrder::query()
                ->whereKey($data['purchase_order_id'])
                ->lockForUpdate()
                ->with('items')
                ->firstOrFail();

            if (! in_array($order->status, [PurchaseOrderStatus::Partial, PurchaseOrderStatus::Received], true)) {
                throw ValidationException::withMessages([
                    'purchase_order_id' => 'Returns are only allowed for partially or fully received orders.',
                ]);
            }

            $lines = [];
            $subtotal = 0.0;

            foreach ($data['items'] as $index => $row) {
                $item = $order->items->firstWhere('id', (int) $row['purchase_order_item_id']);

                if ($item === null) {
                    throw ValidationException::withMessages([
                        "items.{$index}.purchase_order_item_id" => 'Invalid purchase order line.',
                    ]);
                }

                $qty = (float) $row['quantity'];

                if ($qty <= 0) {
                    continue;
                }

                $alreadyReturned = (float) PurchaseReturnItem::query()
                    ->where('purchase_order_item_id', $item->id)
                    ->sum('quantity');

                $returnable = (float) $item->quantity_received - $alreadyReturned;

                if ($qty > $returnable + 0.00005) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => "Cannot return more than {$returnable} for {$item->name}.",
                    ]);
                }

                if (! $item->product_id) {
                    throw ValidationException::withMessages([
                        "items.{$index}.purchase_order_item_id" => "Line {$item->name} is missing product reference.",
                    ]);
                }

                $lineTotal = $qty * (float) $item->unit_cost;
                $subtotal += $lineTotal;

                $lines[] = [
                    'purchase_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'quantity' => number_format($qty, 4, '.', ''),
                    'unit_cost' => (string) $item->unit_cost,
                    'line_total' => number_format($lineTotal, 4, '.', ''),
                    'currency' => $item->currency ?? $order->currency,
                ];
            }

            if ($lines === []) {
                throw ValidationException::withMessages([
                    'items' => 'Enter a return quantity for at least one line.',
                ]);
            }

            $return = PurchaseReturn::query()->create([
                'number' => $this->nextNumber(),
                'status' => 'completed',
                'purchase_order_id' => $order->id,
                'supplier_id' => $order->supplier_id,
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

                // Reverse the purchase receive: reduce on-hand stock.
                $this->stock->fulfill(
                    productId: $line['product_id'],
                    quantity: (float) $line['quantity'],
                    productVariantId: $line['product_variant_id'],
                    warehouseId: $order->warehouse_id,
                    referenceType: PurchaseReturn::class,
                    referenceId: $return->id,
                    userId: $userId,
                );
            }

            return $return->fresh(['items', 'supplier', 'order']);
        });
    }

    public function delete(PurchaseReturn $return): void
    {
        throw ValidationException::withMessages([
            'return' => 'Completed purchase returns cannot be deleted. Create a new receive if needed.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function format(PurchaseReturn $return): array
    {
        return [
            'id' => $return->id,
            'number' => $return->number,
            'status' => $return->status,
            'purchase_order_id' => $return->purchase_order_id,
            'order_number' => $return->order?->number,
            'supplier_name' => $return->supplier?->name,
            'currency' => $return->currency,
            'grand_total' => (string) $return->grand_total,
            'reason' => $return->reason,
            'items_count' => $return->items_count ?? $return->items()->count(),
            'returned_at' => $return->returned_at?->toIso8601String(),
        ];
    }

    private function nextNumber(): string
    {
        $seq = PurchaseReturn::query()->lockForUpdate()->count() + 1;

        return 'PR-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
