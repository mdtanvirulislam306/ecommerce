<?php

namespace Modules\Purchase\Services;

use App\Core\Contracts\StockAvailability;
use App\Core\Events\PurchaseGoodsReceived;
use App\Core\Support\Service;
use App\Core\Tenant\TenantQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderItem;
use Modules\Purchase\Models\Supplier;

class PurchaseOrderService extends Service
{
    public function __construct(
        private readonly StockAvailability $stock,
    ) {}

    /**
     * @return array{draft: int, pending: int, approved: int, partial: int, received: int, cancelled: int}
     */
    public function overviewStats(): array
    {
        $counts = PurchaseOrder::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'draft' => (int) ($counts[PurchaseOrderStatus::Draft->value] ?? 0),
            'pending' => (int) ($counts[PurchaseOrderStatus::Pending->value] ?? 0),
            'approved' => (int) ($counts[PurchaseOrderStatus::Approved->value] ?? 0),
            'partial' => (int) ($counts[PurchaseOrderStatus::Partial->value] ?? 0),
            'received' => (int) ($counts[PurchaseOrderStatus::Received->value] ?? 0),
            'cancelled' => (int) ($counts[PurchaseOrderStatus::Cancelled->value] ?? 0),
            'suppliers' => Supplier::query()->where('is_active', true)->count(),
        ];
    }

    public function listPaginated(
        ?string $search = null,
        ?PurchaseOrderStatus $status = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return PurchaseOrder::query()
            ->with('supplier:id,name,code')
            ->withCount('items')
            ->when($status, fn ($query, $status) => $query->where('status', $status->value))
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($supplier) => $supplier
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"));
            }))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (PurchaseOrder $order) => $this->formatForList($order));
    }

    public function listReceivable(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return PurchaseOrder::query()
            ->with('supplier:id,name,code')
            ->withCount('items')
            ->whereIn('status', [PurchaseOrderStatus::Approved->value, PurchaseOrderStatus::Partial->value])
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($supplier) => $supplier
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"));
            }))
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (PurchaseOrder $order) => $this->formatForList($order));
    }

    /**
     * @param  array{
     *     supplier_id: int,
     *     warehouse_id?: int|null,
     *     notes?: string|null,
     *     status?: string,
     *     currency?: string,
     *     items: list<array{
     *         product_id: int,
     *         product_variant_id?: int|null,
     *         quantity: float|int|string,
     *         unit_cost: float|int|string
     *     }>
     * }  $data
     */
    public function create(array $data, ?int $userId = null): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $status = PurchaseOrderStatus::tryFrom($data['status'] ?? PurchaseOrderStatus::Draft->value)
                ?? PurchaseOrderStatus::Draft;

            if (in_array($status, [PurchaseOrderStatus::Received, PurchaseOrderStatus::Partial, PurchaseOrderStatus::Cancelled], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Invalid status for a new purchase order.',
                ]);
            }

            $lines = $this->buildLines($data['items']);
            $subtotal = $this->sumLines($lines);

            $order = PurchaseOrder::query()->create([
                'number' => $this->nextNumber(),
                'status' => $status === PurchaseOrderStatus::Approved ? PurchaseOrderStatus::Pending : $status,
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'currency' => $data['currency'] ?? 'BDT',
                'subtotal' => $subtotal,
                'grand_total' => $subtotal,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($lines as $index => $line) {
                $order->items()->create([
                    ...$line,
                    'sort_order' => $index,
                ]);
            }

            if (($data['status'] ?? null) === PurchaseOrderStatus::Approved->value) {
                return $this->approve($order->fresh(['items']), $userId);
            }

            return $order->fresh(['items', 'supplier']);
        });
    }

    public function approve(PurchaseOrder $order, ?int $userId = null): PurchaseOrder
    {
        return DB::transaction(function () use ($order) {
            $order = PurchaseOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $order->status->canTransitionTo(PurchaseOrderStatus::Approved)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot approve a {$order->status->label()} purchase order.",
                ]);
            }

            $order->update([
                'status' => PurchaseOrderStatus::Approved,
                'approved_at' => now(),
            ]);

            return $order->fresh(['items', 'supplier']);
        });
    }

    /**
     * @param  list<array{id: int, quantity: float|int|string}>  $receipts
     */
    public function receive(PurchaseOrder $order, array $receipts, ?int $userId = null): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $receipts, $userId) {
            $order = PurchaseOrder::query()->whereKey($order->id)->lockForUpdate()->with('items')->firstOrFail();

            if (! $order->status->canReceive()) {
                throw ValidationException::withMessages([
                    'status' => "Cannot receive against a {$order->status->label()} purchase order.",
                ]);
            }

            $receivedAnything = false;
            $receivedAmount = 0.0;

            foreach ($receipts as $receipt) {
                $item = $order->items->firstWhere('id', (int) $receipt['id']);

                if ($item === null) {
                    throw ValidationException::withMessages([
                        'items' => 'Invalid purchase order line.',
                    ]);
                }

                $qty = (float) $receipt['quantity'];

                if ($qty <= 0) {
                    continue;
                }

                $remaining = $item->quantityRemaining();

                if ($qty > $remaining + 0.00005) {
                    throw ValidationException::withMessages([
                        'items' => "Cannot receive more than remaining qty for {$item->name}.",
                    ]);
                }

                if (! $item->product_id) {
                    throw ValidationException::withMessages([
                        'items' => "Line {$item->name} is missing product reference.",
                    ]);
                }

                $this->stock->receive(
                    productId: $item->product_id,
                    quantity: $qty,
                    productVariantId: $item->product_variant_id,
                    warehouseId: $order->warehouse_id,
                    referenceType: PurchaseOrder::class,
                    referenceId: $order->id,
                    userId: $userId,
                );

                $item->quantity_received = number_format((float) $item->quantity_received + $qty, 4, '.', '');
                $item->save();
                $receivedAnything = true;
                $receivedAmount += $qty * (float) $item->unit_cost;
            }

            if (! $receivedAnything) {
                throw ValidationException::withMessages([
                    'items' => 'Enter a receive quantity for at least one line.',
                ]);
            }

            $order->load('items');
            $allReceived = $order->items->every(fn (PurchaseOrderItem $item) => $item->quantityRemaining() <= 0.00005);

            $order->update([
                'status' => $allReceived ? PurchaseOrderStatus::Received : PurchaseOrderStatus::Partial,
                'received_at' => $allReceived ? now() : $order->received_at,
            ]);

            $order = $order->fresh(['items', 'supplier']);

            event(new PurchaseGoodsReceived(
                orderId: $order->id,
                orderNumber: $order->number,
                amount: number_format($receivedAmount, 4, '.', ''),
                currency: $order->currency,
                supplierName: $order->supplier?->name,
            ));

            return $order;
        });
    }

    public function cancel(PurchaseOrder $order): PurchaseOrder
    {
        return DB::transaction(function () use ($order) {
            $order = PurchaseOrder::query()->whereKey($order->id)->lockForUpdate()->with('items')->firstOrFail();

            if ($order->items->contains(fn (PurchaseOrderItem $item) => (float) $item->quantity_received > 0)) {
                throw ValidationException::withMessages([
                    'status' => 'Cannot cancel a purchase order that already has received stock.',
                ]);
            }

            if (! $order->status->canTransitionTo(PurchaseOrderStatus::Cancelled)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot cancel a {$order->status->label()} purchase order.",
                ]);
            }

            $order->update([
                'status' => PurchaseOrderStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            return $order->fresh(['items', 'supplier']);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function formatForDetail(PurchaseOrder $order): array
    {
        $order->loadMissing(['items', 'supplier:id,name,code,email,phone']);

        return [
            ...$this->formatForList($order),
            'warehouse_id' => $order->warehouse_id,
            'notes' => $order->notes,
            'approved_at' => $order->approved_at?->toIso8601String(),
            'received_at' => $order->received_at?->toIso8601String(),
            'cancelled_at' => $order->cancelled_at?->toIso8601String(),
            'can_approve' => $order->status->canTransitionTo(PurchaseOrderStatus::Approved),
            'can_receive' => $order->status->canReceive(),
            'can_cancel' => $order->status->canTransitionTo(PurchaseOrderStatus::Cancelled)
                && $order->items->every(fn (PurchaseOrderItem $item) => (float) $item->quantity_received <= 0),
            'supplier' => $order->supplier ? [
                'id' => $order->supplier->id,
                'name' => $order->supplier->name,
                'code' => $order->supplier->code,
                'email' => $order->supplier->email,
                'phone' => $order->supplier->phone,
            ] : null,
            'items' => $order->items->map(fn (PurchaseOrderItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'sku' => $item->sku,
                'name' => $item->name,
                'quantity_ordered' => (string) $item->quantity_ordered,
                'quantity_received' => (string) $item->quantity_received,
                'quantity_remaining' => number_format($item->quantityRemaining(), 4, '.', ''),
                'unit_cost' => (string) $item->unit_cost,
                'line_total' => (string) $item->line_total,
                'currency' => $item->currency,
            ])->all(),
        ];
    }

    /**
     * @return list<array{id: int, name: string, sku: ?string, type: string}>
     */
    public function searchProducts(?string $search = null, int $limit = 40): array
    {
        $query = TenantQuery::constrain(DB::table('products'), 'products')
            ->select(['id', 'name', 'sku', 'type'])
            ->where('status', '!=', 'archived')
            ->orderBy('name')
            ->limit($limit);

        if ($search) {
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return $query->get()->map(fn ($row) => [
            'id' => $row->id,
            'name' => $row->name,
            'sku' => $row->sku,
            'type' => $row->type,
        ])->all();
    }

    /**
     * @return list<array{id: int, sku: string, name: ?string}>
     */
    public function variantsForProduct(int $productId): array
    {
        return DB::table('product_variants')
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'sku', 'name'])
            ->map(fn ($row) => [
                'id' => $row->id,
                'sku' => $row->sku,
                'name' => $row->name,
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, code: string, is_default: bool}>
     */
    public function warehouses(): array
    {
        return DB::table('warehouses')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'code', 'is_default'])
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'code' => $row->code,
                'is_default' => (bool) $row->is_default,
            ])
            ->all();
    }

    /**
     * @param  list<array{product_id: int, product_variant_id?: int|null, quantity: float|int|string, unit_cost: float|int|string}>  $items
     * @return list<array<string, mixed>>
     */
    private function buildLines(array $items): array
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one line item.',
            ]);
        }

        $lines = [];

        foreach ($items as $index => $item) {
            $productId = (int) $item['product_id'];
            $variantId = ! empty($item['product_variant_id']) ? (int) $item['product_variant_id'] : null;
            $quantity = (float) $item['quantity'];
            $unitCost = (float) $item['unit_cost'];

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => 'Quantity must be greater than zero.',
                ]);
            }

            if ($unitCost < 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_cost" => 'Unit cost cannot be negative.',
                ]);
            }

            $product = TenantQuery::constrain(DB::table('products'), 'products')->where('id', $productId)->first();

            if ($product === null || $product->status === 'archived') {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'Product not found or archived.',
                ]);
            }

            $sku = $product->sku;
            $name = $product->name;

            if ($variantId) {
                $variant = DB::table('product_variants')
                    ->where('id', $variantId)
                    ->where('product_id', $productId)
                    ->first();

                if ($variant === null) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_variant_id" => 'Invalid variant for this product.',
                    ]);
                }

                $sku = $variant->sku;
                $name = trim($product->name.($variant->name ? ' — '.$variant->name : ' — '.$variant->sku));
            }

            $lines[] = [
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'sku' => $sku,
                'name' => $name,
                'quantity_ordered' => number_format($quantity, 4, '.', ''),
                'quantity_received' => '0.0000',
                'unit_cost' => number_format($unitCost, 4, '.', ''),
                'line_total' => number_format($quantity * $unitCost, 4, '.', ''),
                'currency' => 'BDT',
            ];
        }

        return $lines;
    }

    /**
     * @param  list<array{line_total: string}>  $lines
     */
    private function sumLines(array $lines): string
    {
        $subtotal = 0.0;

        foreach ($lines as $line) {
            $subtotal += (float) $line['line_total'];
        }

        return number_format($subtotal, 4, '.', '');
    }

    private function nextNumber(): string
    {
        $seq = PurchaseOrder::query()->lockForUpdate()->count() + 1;

        return 'PO-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatForList(PurchaseOrder $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'supplier_name' => $order->supplier?->name,
            'currency' => $order->currency,
            'grand_total' => (string) $order->grand_total,
            'items_count' => $order->items_count ?? $order->items()->count(),
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }
}
