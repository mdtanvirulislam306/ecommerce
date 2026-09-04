<?php

namespace Modules\Pos\Services;

use App\Core\Contracts\PriceResolver;
use App\Core\Contracts\StockAvailability;
use App\Core\Events\PosSaleCancelled;
use App\Core\Events\PosSaleCompleted;
use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pos\Enums\PosOrderStatus;
use Modules\Pos\Models\PosOrder;
use Modules\Pos\Models\PosOrderItem;
use Modules\Pos\Models\PosRegister;

class PosSaleService extends Service
{
    public function __construct(
        private readonly PriceResolver $prices,
        private readonly StockAvailability $stock,
        private readonly PosRegisterService $registers,
    ) {}

    /**
     * @return array{completed_today: int, cancelled_today: int, revenue_today: string, open_sessions: int}
     */
    public function overviewStats(): array
    {
        $today = now()->toDateString();

        return [
            'completed_today' => PosOrder::query()
                ->where('status', PosOrderStatus::Completed)
                ->whereDate('completed_at', $today)
                ->count(),
            'cancelled_today' => PosOrder::query()
                ->where('status', PosOrderStatus::Cancelled)
                ->whereDate('cancelled_at', $today)
                ->count(),
            'revenue_today' => number_format(
                (float) PosOrder::query()
                    ->where('status', PosOrderStatus::Completed)
                    ->whereDate('completed_at', $today)
                    ->sum('grand_total'),
                2,
                '.',
                '',
            ),
            'open_sessions' => DB::table('pos_sessions')->where('status', 'open')->count(),
        ];
    }

    public function listPaginated(?string $search = null, ?PosOrderStatus $status = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return PosOrder::query()
            ->withCount('items')
            ->when($status, fn ($query, $status) => $query->where('status', $status->value))
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (PosOrder $order) => $this->formatList($order));
    }

    /**
     * @return list<array{id: int, name: string, sku: ?string, type: string, price: ?string, currency: string, stock_available: string, in_stock: bool}>
     */
    public function searchableProducts(?string $search = null, int $limit = 30): array
    {
        $query = DB::table('products')
            ->where('status', '!=', 'archived')
            ->whereIn('status', ['active', 'approved'])
            ->orderBy('name')
            ->limit($limit);

        if ($search) {
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        return $query->get(['id', 'name', 'sku', 'barcode', 'type'])->map(function ($row) {
            $resolved = $this->prices->resolve(productId: $row->id, quantity: 1);
            $available = $this->stock->available($row->id);

            return [
                'id' => $row->id,
                'name' => $row->name,
                'sku' => $row->sku,
                'barcode' => $row->barcode,
                'type' => $row->type,
                'price' => $resolved['resolved'] ? $resolved['price'] : null,
                'currency' => $resolved['currency'] ?? 'BDT',
                'stock_available' => $available['available'],
                'in_stock' => (float) $available['available'] > 0,
            ];
        })->all();
    }

    /**
     * Exact barcode/SKU match for scanner Enter-to-add.
     *
     * @return array{id: int, name: string, sku: ?string, barcode: ?string, type: string, price: ?string, currency: string, stock_available: string, in_stock: bool}|null
     */
    public function findByBarcode(string $code): ?array
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        $row = DB::table('products')
            ->where('status', '!=', 'archived')
            ->whereIn('status', ['active', 'approved'])
            ->where(function ($inner) use ($code) {
                $inner->where('barcode', $code)->orWhere('sku', $code);
            })
            ->first(['id', 'name', 'sku', 'barcode', 'type']);

        if ($row === null) {
            return null;
        }

        $resolved = $this->prices->resolve(productId: $row->id, quantity: 1);
        $available = $this->stock->available($row->id);

        return [
            'id' => $row->id,
            'name' => $row->name,
            'sku' => $row->sku,
            'barcode' => $row->barcode,
            'type' => $row->type,
            'price' => $resolved['resolved'] ? $resolved['price'] : null,
            'currency' => $resolved['currency'] ?? 'BDT',
            'stock_available' => $available['available'],
            'in_stock' => (float) $available['available'] > 0,
        ];
    }

    /**
     * @param  array{
     *     pos_register_id?: int|null,
     *     customer_name?: string|null,
     *     amount_tendered?: float|int|string|null,
     *     notes?: string|null,
     *     items: list<array{product_id: int, product_variant_id?: int|null, quantity: float|int|string}>
     * }  $data
     */
    public function completeSale(array $data, ?int $userId = null): PosOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $register = ! empty($data['pos_register_id'])
                ? PosRegister::query()->findOrFail($data['pos_register_id'])
                : $this->registers->ensureDefault();

            $session = $register->openSession;

            if ($session === null) {
                $session = $this->registers->openSession($register, 0, $userId);
            }

            $warehouseId = $register->warehouse_id
                ?? DB::table('warehouses')->where('is_default', true)->value('id')
                ?? DB::table('warehouses')->orderBy('id')->value('id');

            $lines = $this->buildLines($data['items'] ?? []);
            $subtotal = $this->sumLines($lines);
            $tendered = isset($data['amount_tendered']) ? (float) $data['amount_tendered'] : (float) $subtotal;

            if ($tendered + 0.00005 < (float) $subtotal) {
                throw ValidationException::withMessages([
                    'amount_tendered' => 'Amount tendered is less than the total.',
                ]);
            }

            foreach ($lines as $line) {
                if (! $this->stock->canFulfill($line['product_id'], (float) $line['quantity'], $line['product_variant_id'], $warehouseId)) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for {$line['name']}.",
                    ]);
                }
            }

            $order = PosOrder::query()->create([
                'number' => $this->nextNumber(),
                'status' => PosOrderStatus::Completed,
                'pos_register_id' => $register->id,
                'pos_session_id' => $session->id,
                'warehouse_id' => $warehouseId,
                'customer_name' => $data['customer_name'] ?? 'Walk-in',
                'payment_method' => 'cash',
                'currency' => $lines[0]['currency'] ?? 'BDT',
                'subtotal' => $subtotal,
                'grand_total' => $subtotal,
                'amount_tendered' => number_format($tendered, 4, '.', ''),
                'change_due' => number_format($tendered - (float) $subtotal, 4, '.', ''),
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
                'completed_at' => now(),
            ]);

            foreach ($lines as $index => $line) {
                $order->items()->create([
                    ...$line,
                    'sort_order' => $index,
                ]);

                $this->stock->fulfill(
                    productId: $line['product_id'],
                    quantity: (float) $line['quantity'],
                    productVariantId: $line['product_variant_id'],
                    warehouseId: $warehouseId,
                    referenceType: PosOrder::class,
                    referenceId: $order->id,
                    userId: $userId,
                );
            }

            $order = $order->fresh(['items']);

            event(new PosSaleCompleted(
                orderId: $order->id,
                orderNumber: $order->number,
                amount: (string) $order->grand_total,
                currency: $order->currency,
                customerName: $order->customer_name,
            ));

            return $order;
        });
    }

    public function cancel(PosOrder $order, ?int $userId = null): PosOrder
    {
        return DB::transaction(function () use ($order, $userId) {
            $order = PosOrder::query()->whereKey($order->id)->lockForUpdate()->with('items')->firstOrFail();

            if (! $order->status->canTransitionTo(PosOrderStatus::Cancelled)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot cancel a {$order->status->label()} POS order.",
                ]);
            }

            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $this->stock->restock(
                    productId: $item->product_id,
                    quantity: (float) $item->quantity,
                    productVariantId: $item->product_variant_id,
                    warehouseId: $order->warehouse_id,
                    referenceType: PosOrder::class,
                    referenceId: $order->id,
                    userId: $userId,
                );
            }

            $order->update([
                'status' => PosOrderStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            $order = $order->fresh(['items']);

            event(new PosSaleCancelled(
                orderId: $order->id,
                orderNumber: $order->number,
                amount: (string) $order->grand_total,
                currency: $order->currency,
                customerName: $order->customer_name,
            ));

            return $order;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function formatDetail(PosOrder $order): array
    {
        $order->loadMissing(['items', 'register:id,name,code']);

        return [
            ...$this->formatList($order),
            'pos_register_id' => $order->pos_register_id,
            'register_name' => $order->register?->name,
            'customer_name' => $order->customer_name,
            'payment_method' => $order->payment_method,
            'amount_tendered' => (string) $order->amount_tendered,
            'change_due' => (string) $order->change_due,
            'notes' => $order->notes,
            'completed_at' => $order->completed_at?->toIso8601String(),
            'cancelled_at' => $order->cancelled_at?->toIso8601String(),
            'can_cancel' => $order->status->canTransitionTo(PosOrderStatus::Cancelled),
            'items' => $order->items->map(fn (PosOrderItem $item) => [
                'id' => $item->id,
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
     * @param  list<array{product_id: int, product_variant_id?: int|null, quantity: float|int|string}>  $items
     * @return list<array<string, mixed>>
     */
    private function buildLines(array $items): array
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one product.',
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
                    "items.{$index}.product_id" => 'Product not found.',
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
                        "items.{$index}.product_variant_id" => 'Invalid variant.',
                    ]);
                }

                $sku = $variant->sku;
                $name = trim($product->name.($variant->name ? ' — '.$variant->name : ' — '.$variant->sku));
            }

            $resolved = $this->prices->resolve(
                productId: $productId,
                quantity: (int) max(1, floor($quantity)),
                productVariantId: $variantId,
            );

            if (! $resolved['resolved'] || $resolved['price'] === null) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => $resolved['message'] ?? 'Price not available.',
                ]);
            }

            $unit = (float) $resolved['price'];

            $lines[] = [
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'sku' => $sku,
                'name' => $name,
                'quantity' => number_format($quantity, 4, '.', ''),
                'unit_price' => number_format($unit, 4, '.', ''),
                'line_total' => number_format($unit * $quantity, 4, '.', ''),
                'currency' => $resolved['currency'] ?? 'BDT',
            ];
        }

        return $lines;
    }

    /**
     * @param  list<array{line_total: string}>  $lines
     */
    private function sumLines(array $lines): string
    {
        $total = 0.0;

        foreach ($lines as $line) {
            $total += (float) $line['line_total'];
        }

        return number_format($total, 4, '.', '');
    }

    private function nextNumber(): string
    {
        $seq = PosOrder::query()->lockForUpdate()->count() + 1;

        return 'POS-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatList(PosOrder $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'customer_name' => $order->customer_name,
            'currency' => $order->currency,
            'grand_total' => (string) $order->grand_total,
            'items_count' => $order->items_count ?? $order->items()->count(),
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }
}
