<?php

namespace Modules\Sales\Services;

use App\Core\Contracts\PriceResolver;
use App\Core\Contracts\StockAvailability;
use App\Core\Events\SalesOrderCancelled;
use App\Core\Events\SalesOrderConfirmed;
use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;

class SalesOrderService extends Service
{
    public function __construct(
        private readonly PriceResolver $prices,
        private readonly StockAvailability $stock,
    ) {}

    /**
     * @return array{draft: int, pending: int, confirmed: int, cancelled: int, revenue: string}
     */
    public function overviewStats(): array
    {
        $counts = SalesOrder::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'draft' => (int) ($counts[SalesOrderStatus::Draft->value] ?? 0),
            'pending' => (int) ($counts[SalesOrderStatus::Pending->value] ?? 0),
            'confirmed' => (int) ($counts[SalesOrderStatus::Confirmed->value] ?? 0),
            'cancelled' => (int) ($counts[SalesOrderStatus::Cancelled->value] ?? 0),
            'revenue' => number_format(
                (float) SalesOrder::query()
                    ->where('status', SalesOrderStatus::Confirmed->value)
                    ->sum('grand_total'),
                2,
                '.',
                '',
            ),
        ];
    }

    public function listPaginated(
        ?string $search = null,
        ?SalesOrderStatus $status = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return SalesOrder::query()
            ->withCount('items')
            ->when($status, fn ($query, $status) => $query->where('status', $status->value))
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (SalesOrder $order) => $this->formatForList($order));
    }

    /**
     * @param  array{
     *     customer_name: string,
     *     customer_email?: string|null,
     *     customer_phone?: string|null,
     *     customer_group_id?: int|null,
     *     warehouse_id?: int|null,
     *     notes?: string|null,
     *     status?: string,
     *     items: list<array{product_id: int, product_variant_id?: int|null, quantity: float|int|string}>
     * }  $data
     */
    public function create(array $data, ?int $userId = null): SalesOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $status = SalesOrderStatus::tryFrom($data['status'] ?? SalesOrderStatus::Draft->value)
                ?? SalesOrderStatus::Draft;

            if ($status === SalesOrderStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'status' => 'Cannot create an order as cancelled.',
                ]);
            }

            $lines = $this->buildSnapshotLines(
                $data['items'],
                ! empty($data['customer_group_id']) ? (int) $data['customer_group_id'] : null,
            );

            if ($status === SalesOrderStatus::Confirmed) {
                $this->assertStock($lines, ! empty($data['warehouse_id']) ? (int) $data['warehouse_id'] : null);
            }

            $totals = $this->sumLines($lines);

            $order = SalesOrder::query()->create([
                'number' => $this->nextNumber(),
                'status' => $status === SalesOrderStatus::Confirmed ? SalesOrderStatus::Pending : $status,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_group_id' => $data['customer_group_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'currency' => $totals['currency'],
                'subtotal' => $totals['subtotal'],
                'tax_total' => 0,
                'grand_total' => $totals['subtotal'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($lines as $index => $line) {
                $order->items()->create([
                    ...$line,
                    'sort_order' => $index,
                ]);
            }

            if (($data['status'] ?? null) === SalesOrderStatus::Confirmed->value) {
                return $this->confirm($order->fresh(['items']), $userId);
            }

            return $order->fresh(['items']);
        });
    }

    public function confirm(SalesOrder $order, ?int $userId = null): SalesOrder
    {
        try {
            return DB::transaction(function () use ($order, $userId) {
                $order = SalesOrder::query()->whereKey($order->id)->lockForUpdate()->with('items')->firstOrFail();

                if (! $order->status->canTransitionTo(SalesOrderStatus::Confirmed)) {
                    throw ValidationException::withMessages([
                        'status' => "Cannot confirm a {$order->status->label()} order.",
                    ]);
                }

                if ($order->items->isEmpty()) {
                    throw ValidationException::withMessages([
                        'items' => 'Order has no line items.',
                    ]);
                }

                $lines = $order->items->map(fn (SalesOrderItem $item) => [
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => (float) $item->quantity,
                    'name' => $item->name,
                ])->all();

                $this->assertStock($lines, $order->warehouse_id);

                foreach ($order->items as $item) {
                    if (! $item->product_id) {
                        continue;
                    }

                    $this->stock->fulfill(
                        productId: $item->product_id,
                        quantity: (float) $item->quantity,
                        productVariantId: $item->product_variant_id,
                        warehouseId: $order->warehouse_id,
                        referenceType: SalesOrder::class,
                        referenceId: $order->id,
                        userId: $userId,
                    );
                }

                $order->update([
                    'status' => SalesOrderStatus::Confirmed,
                    'confirmed_at' => now(),
                ]);

                $order = $order->fresh(['items']);

                event(new SalesOrderConfirmed(
                    orderId: $order->id,
                    orderNumber: $order->number,
                    amount: (string) $order->grand_total,
                    currency: $order->currency,
                    customerName: $order->customer_name,
                ));

                Log::info('sales.order.confirmed', [
                    'order_id' => $order->id,
                    'number' => $order->number,
                    'amount' => $order->grand_total,
                ]);

                return $order;
            });
        } catch (\Throwable $e) {
            Log::error('sales.order.confirm_failed', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function cancel(SalesOrder $order, ?int $userId = null): SalesOrder
    {
        try {
            return DB::transaction(function () use ($order, $userId) {
                $order = SalesOrder::query()->whereKey($order->id)->lockForUpdate()->with('items')->firstOrFail();

                if (! $order->status->canTransitionTo(SalesOrderStatus::Cancelled)) {
                    throw ValidationException::withMessages([
                        'status' => "Cannot cancel a {$order->status->label()} order.",
                    ]);
                }

                $wasConfirmed = $order->status === SalesOrderStatus::Confirmed;

                if ($wasConfirmed) {
                    foreach ($order->items as $item) {
                        if (! $item->product_id) {
                            continue;
                        }

                        $this->stock->restock(
                            productId: $item->product_id,
                            quantity: (float) $item->quantity,
                            productVariantId: $item->product_variant_id,
                            warehouseId: $order->warehouse_id,
                            referenceType: SalesOrder::class,
                            referenceId: $order->id,
                            userId: $userId,
                        );
                    }
                }

                $order->update([
                    'status' => SalesOrderStatus::Cancelled,
                    'cancelled_at' => now(),
                ]);

                $order = $order->fresh(['items']);

                event(new SalesOrderCancelled(
                    orderId: $order->id,
                    orderNumber: $order->number,
                    amount: (string) $order->grand_total,
                    currency: $order->currency,
                    wasConfirmed: $wasConfirmed,
                    customerName: $order->customer_name,
                ));

                Log::info('sales.order.cancelled', [
                    'order_id' => $order->id,
                    'number' => $order->number,
                    'was_confirmed' => $wasConfirmed,
                ]);

                return $order;
            });
        } catch (\Throwable $e) {
            Log::error('sales.order.cancel_failed', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function formatForDetail(SalesOrder $order): array
    {
        $order->loadMissing('items');

        return [
            ...$this->formatForList($order),
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'customer_group_id' => $order->customer_group_id,
            'warehouse_id' => $order->warehouse_id,
            'notes' => $order->notes,
            'confirmed_at' => $order->confirmed_at?->toIso8601String(),
            'cancelled_at' => $order->cancelled_at?->toIso8601String(),
            'can_confirm' => $order->status->canTransitionTo(SalesOrderStatus::Confirmed),
            'can_cancel' => $order->status->canTransitionTo(SalesOrderStatus::Cancelled),
            'items' => $order->items->map(fn (SalesOrderItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'sku' => $item->sku,
                'name' => $item->name,
                'quantity' => (string) $item->quantity,
                'unit_price' => (string) $item->unit_price,
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
        $query = DB::table('products')
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
     * @return list<array{id: int, name: string, code: string}>
     */
    public function customerGroups(): array
    {
        return DB::table('customer_groups')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'code'])
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'code' => $row->code,
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
     * Preview resolved unit price for the create form.
     *
     * @return array<string, mixed>
     */
    public function previewPrice(
        int $productId,
        int $quantity = 1,
        ?int $productVariantId = null,
        ?int $customerGroupId = null,
    ): array {
        return $this->prices->resolve(
            productId: $productId,
            quantity: max(1, $quantity),
            productVariantId: $productVariantId,
            customerGroupId: $customerGroupId,
        );
    }

    /**
     * @param  list<array{product_id: int, product_variant_id?: int|null, quantity: float|int|string}>  $items
     * @return list<array<string, mixed>>
     */
    private function buildSnapshotLines(array $items, ?int $customerGroupId): array
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

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => 'Quantity must be greater than zero.',
                ]);
            }

            $product = DB::table('products')->where('id', $productId)->first();

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

            $resolved = $this->prices->resolve(
                productId: $productId,
                quantity: (int) max(1, ceil($quantity)),
                productVariantId: $variantId,
                customerGroupId: $customerGroupId,
            );

            if (! ($resolved['resolved'] ?? false)) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => $resolved['message'] ?? 'Could not resolve a price for this product.',
                ]);
            }

            $unitPrice = (float) $resolved['price'];
            $lineTotal = $unitPrice * $quantity;

            $lines[] = [
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'sku' => $sku,
                'name' => $name,
                'quantity' => number_format($quantity, 4, '.', ''),
                'unit_price' => number_format($unitPrice, 4, '.', ''),
                'line_total' => number_format($lineTotal, 4, '.', ''),
                'currency' => $resolved['currency'] ?? 'BDT',
            ];
        }

        return $lines;
    }

    /**
     * @param  list<array{product_id?: int|null, product_variant_id?: int|null, quantity: float, name?: string}>  $lines
     */
    private function assertStock(array $lines, ?int $warehouseId): void
    {
        foreach ($lines as $line) {
            if (empty($line['product_id'])) {
                continue;
            }

            $qty = (float) $line['quantity'];

            if (! $this->stock->canFulfill(
                productId: (int) $line['product_id'],
                quantity: $qty,
                productVariantId: $line['product_variant_id'] ?? null,
                warehouseId: $warehouseId,
            )) {
                $label = $line['name'] ?? 'Product #'.$line['product_id'];

                throw ValidationException::withMessages([
                    'items' => "Insufficient stock for {$label}.",
                ]);
            }
        }
    }

    /**
     * @param  list<array{line_total: string, currency: string}>  $lines
     * @return array{subtotal: string, currency: string}
     */
    private function sumLines(array $lines): array
    {
        $subtotal = 0.0;
        $currency = $lines[0]['currency'] ?? 'BDT';

        foreach ($lines as $line) {
            $subtotal += (float) $line['line_total'];
        }

        return [
            'subtotal' => number_format($subtotal, 4, '.', ''),
            'currency' => $currency,
        ];
    }

    private function nextNumber(): string
    {
        $seq = SalesOrder::query()->lockForUpdate()->count() + 1;

        return 'SO-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatForList(SalesOrder $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'customer_name' => $order->customer_name,
            'currency' => $order->currency,
            'subtotal' => (string) $order->subtotal,
            'grand_total' => (string) $order->grand_total,
            'items_count' => $order->items_count ?? $order->items()->count(),
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }
}
