<?php

namespace Modules\Sales\Services;

use App\Core\Contracts\StockAvailability;
use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Sales\Enums\SalesReturnStatus;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesReturn;
use Modules\Sales\Models\SalesReturnItem;

class SalesReturnService extends Service
{
    public function __construct(
        private readonly StockAvailability $stock,
    ) {}

    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return SalesReturn::query()
            ->withCount('items')
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (SalesReturn $return) => $this->formatForList($return));
    }

    /**
     * @param  array{
     *     sales_invoice_id?: int|null,
     *     sales_order_id?: int|null,
     *     warehouse_id?: int|null,
     *     customer_name?: string|null,
     *     notes?: string|null,
     *     confirm?: bool,
     *     items: list<array{
     *         product_id?: int|null,
     *         product_variant_id?: int|null,
     *         sku?: string|null,
     *         name: string,
     *         quantity: float|int|string,
     *         unit_price: float|int|string
     *     }>
     * }  $data
     */
    public function create(array $data, ?int $userId = null): SalesReturn
    {
        return DB::transaction(function () use ($data, $userId) {
            $invoiceId = ! empty($data['sales_invoice_id']) ? (int) $data['sales_invoice_id'] : null;
            $orderId = ! empty($data['sales_order_id']) ? (int) $data['sales_order_id'] : null;

            if (! $invoiceId && ! $orderId) {
                throw ValidationException::withMessages([
                    'sales_invoice_id' => 'Select an invoice or sales order for this return.',
                ]);
            }

            $customerName = $data['customer_name'] ?? null;
            $warehouseId = $data['warehouse_id'] ?? null;
            $currency = 'BDT';

            if ($invoiceId) {
                $invoice = SalesInvoice::query()->with('items')->findOrFail($invoiceId);
                $customerName ??= $invoice->customer_name;
                $currency = $invoice->currency;
                $orderId ??= $invoice->sales_order_id;
            }

            if ($orderId) {
                $order = SalesOrder::query()->findOrFail($orderId);
                $customerName ??= $order->customer_name;
                $warehouseId ??= $order->warehouse_id;
                $currency = $order->currency;
            }

            $lines = $this->normalizeLines($data['items'] ?? [], $currency);
            $totals = $this->sumLines($lines);

            $return = SalesReturn::query()->create([
                'number' => $this->nextNumber(),
                'sales_invoice_id' => $invoiceId,
                'sales_order_id' => $orderId,
                'status' => SalesReturnStatus::Draft,
                'warehouse_id' => $warehouseId,
                'customer_name' => $customerName,
                'currency' => $totals['currency'],
                'subtotal' => $totals['subtotal'],
                'grand_total' => $totals['subtotal'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($lines as $index => $line) {
                $return->items()->create([
                    ...$line,
                    'sort_order' => $index,
                ]);
            }

            if (! empty($data['confirm'])) {
                return $this->confirm($return->fresh(['items']), $userId);
            }

            return $return->fresh(['items']);
        });
    }

    public function confirm(SalesReturn $return, ?int $userId = null): SalesReturn
    {
        return DB::transaction(function () use ($return, $userId) {
            $return = SalesReturn::query()->whereKey($return->id)->lockForUpdate()->with('items')->firstOrFail();

            if (! $return->status->canConfirm()) {
                throw ValidationException::withMessages([
                    'status' => "Cannot confirm a {$return->status->label()} return.",
                ]);
            }

            if ($return->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Return has no line items.',
                ]);
            }

            foreach ($return->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $this->stock->restock(
                    productId: $item->product_id,
                    quantity: (float) $item->quantity,
                    productVariantId: $item->product_variant_id,
                    warehouseId: $return->warehouse_id,
                    referenceType: SalesReturn::class,
                    referenceId: $return->id,
                    userId: $userId,
                );
            }

            $return->update([
                'status' => SalesReturnStatus::Confirmed,
                'confirmed_at' => now(),
            ]);

            Log::info('sales.return.confirmed', [
                'return_id' => $return->id,
                'number' => $return->number,
            ]);

            return $return->fresh(['items']);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function formatForDetail(SalesReturn $return): array
    {
        $return->loadMissing('items');

        return [
            ...$this->formatForList($return),
            'notes' => $return->notes,
            'warehouse_id' => $return->warehouse_id,
            'sales_invoice_id' => $return->sales_invoice_id,
            'sales_order_id' => $return->sales_order_id,
            'confirmed_at' => $return->confirmed_at?->toIso8601String(),
            'can_confirm' => $return->status->canConfirm(),
            'items' => $return->items->map(fn (SalesReturnItem $item) => [
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
     * @return array<string, mixed>
     */
    public function formatForList(SalesReturn $return): array
    {
        return [
            'id' => $return->id,
            'number' => $return->number,
            'status' => $return->status->value,
            'status_label' => $return->status->label(),
            'customer_name' => $return->customer_name,
            'currency' => $return->currency,
            'grand_total' => (string) $return->grand_total,
            'items_count' => $return->items_count ?? $return->items()->count(),
            'created_at' => $return->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function normalizeLines(array $items, string $currency): array
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one return line.',
            ]);
        }

        $lines = [];

        foreach ($items as $index => $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $name = trim((string) ($item['name'] ?? ''));

            if ($name === '') {
                throw ValidationException::withMessages([
                    "items.{$index}.name" => 'Line name is required.',
                ]);
            }

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => 'Quantity must be greater than zero.',
                ]);
            }

            $productId = ! empty($item['product_id']) ? (int) $item['product_id'] : null;
            $variantId = ! empty($item['product_variant_id']) ? (int) $item['product_variant_id'] : null;
            $sku = $item['sku'] ?? null;

            if ($productId) {
                $product = DB::table('products')->where('id', $productId)->first();

                if ($product === null) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => 'Product not found.',
                    ]);
                }

                $sku ??= $product->sku;
                $name = $name ?: $product->name;
            }

            $lines[] = [
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'sku' => $sku,
                'name' => $name,
                'quantity' => number_format($quantity, 4, '.', ''),
                'unit_price' => number_format($unitPrice, 4, '.', ''),
                'line_total' => number_format($unitPrice * $quantity, 4, '.', ''),
                'currency' => $currency,
            ];
        }

        return $lines;
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
        $seq = SalesReturn::query()->lockForUpdate()->count() + 1;

        return 'SR-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
