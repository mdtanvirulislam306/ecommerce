<?php

namespace Modules\Sales\Services;

use App\Core\Contracts\PriceResolver;
use App\Core\Support\Service;
use App\Core\Tenant\TenantQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Crm\Services\CustomerService;
use Modules\Sales\Enums\QuotationStatus;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Models\SalesQuotation;
use Modules\Sales\Models\SalesQuotationItem;

class QuotationService extends Service
{
    public function __construct(
        private readonly PriceResolver $prices,
        private readonly SalesOrderService $orders,
        private readonly CustomerService $customers,
    ) {}

    public function listPaginated(
        ?string $search = null,
        ?QuotationStatus $status = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        $this->expirePastDue();

        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return SalesQuotation::query()
            ->withCount('items')
            ->when($status, fn ($query, $status) => $query->where('status', $status->value))
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (SalesQuotation $quotation) => $this->formatForList($quotation));
    }

    /**
     * @param  array{
     *     customer_name: string,
     *     customer_email?: string|null,
     *     customer_phone?: string|null,
     *     customer_group_id?: int|null,
     *     warehouse_id?: int|null,
     *     notes?: string|null,
     *     valid_until?: string|null,
     *     status?: string,
     *     items: list<array{product_id: int, product_variant_id?: int|null, quantity: float|int|string}>
     * }  $data
     */
    public function create(array $data, ?int $userId = null): SalesQuotation
    {
        return DB::transaction(function () use ($data, $userId) {
            $data = $this->customers->applySnapshot($data);

            $status = QuotationStatus::tryFrom($data['status'] ?? QuotationStatus::Draft->value)
                ?? QuotationStatus::Draft;

            if ($status === QuotationStatus::Expired) {
                throw ValidationException::withMessages([
                    'status' => 'Cannot create a quotation as expired.',
                ]);
            }

            $lines = $this->buildSnapshotLines(
                $data['items'],
                ! empty($data['customer_group_id']) ? (int) $data['customer_group_id'] : null,
            );

            $totals = $this->sumLines($lines);

            $quotation = SalesQuotation::query()->create([
                'number' => $this->nextNumber(),
                'status' => $status,
                'customer_id' => $data['customer_id'] ?? null,
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
                'valid_until' => $data['valid_until'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($lines as $index => $line) {
                $quotation->items()->create([
                    ...$line,
                    'sort_order' => $index,
                ]);
            }

            return $quotation->fresh(['items']);
        });
    }

    /**
     * @param  array{
     *     customer_name?: string,
     *     customer_email?: string|null,
     *     customer_phone?: string|null,
     *     customer_group_id?: int|null,
     *     warehouse_id?: int|null,
     *     notes?: string|null,
     *     valid_until?: string|null,
     *     status?: string,
     *     items?: list<array{product_id: int, product_variant_id?: int|null, quantity: float|int|string}>
     * }  $data
     */
    public function update(SalesQuotation $quotation, array $data): SalesQuotation
    {
        return DB::transaction(function () use ($quotation, $data) {
            $quotation = SalesQuotation::query()->whereKey($quotation->id)->lockForUpdate()->firstOrFail();

            if (in_array($quotation->status, [QuotationStatus::Accepted, QuotationStatus::Expired], true)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot edit a {$quotation->status->label()} quotation.",
                ]);
            }

            $status = isset($data['status'])
                ? (QuotationStatus::tryFrom($data['status']) ?? $quotation->status)
                : $quotation->status;

            if ($status !== $quotation->status && ! $quotation->status->canTransitionTo($status)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot change status from {$quotation->status->label()} to {$status->label()}.",
                ]);
            }

            $data = $this->customers->applySnapshot([
                ...$data,
                'customer_id' => array_key_exists('customer_id', $data) ? $data['customer_id'] : $quotation->customer_id,
                'customer_name' => $data['customer_name'] ?? $quotation->customer_name,
                'customer_email' => array_key_exists('customer_email', $data) ? $data['customer_email'] : $quotation->customer_email,
                'customer_phone' => array_key_exists('customer_phone', $data) ? $data['customer_phone'] : $quotation->customer_phone,
                'customer_group_id' => array_key_exists('customer_group_id', $data) ? $data['customer_group_id'] : $quotation->customer_group_id,
            ]);

            $payload = [
                'customer_id' => $data['customer_id'] ?? $quotation->customer_id,
                'customer_name' => $data['customer_name'] ?? $quotation->customer_name,
                'customer_email' => array_key_exists('customer_email', $data) ? $data['customer_email'] : $quotation->customer_email,
                'customer_phone' => array_key_exists('customer_phone', $data) ? $data['customer_phone'] : $quotation->customer_phone,
                'customer_group_id' => array_key_exists('customer_group_id', $data) ? $data['customer_group_id'] : $quotation->customer_group_id,
                'warehouse_id' => array_key_exists('warehouse_id', $data) ? $data['warehouse_id'] : $quotation->warehouse_id,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $quotation->notes,
                'valid_until' => array_key_exists('valid_until', $data) ? $data['valid_until'] : $quotation->valid_until,
                'status' => $status,
            ];

            if (! empty($data['items'])) {
                $lines = $this->buildSnapshotLines(
                    $data['items'],
                    ! empty($payload['customer_group_id']) ? (int) $payload['customer_group_id'] : null,
                );
                $totals = $this->sumLines($lines);

                $quotation->items()->delete();
                foreach ($lines as $index => $line) {
                    $quotation->items()->create([
                        ...$line,
                        'sort_order' => $index,
                    ]);
                }

                $payload['currency'] = $totals['currency'];
                $payload['subtotal'] = $totals['subtotal'];
                $payload['grand_total'] = $totals['subtotal'];
            }

            $quotation->update($payload);

            return $quotation->fresh(['items']);
        });
    }

    public function markStatus(SalesQuotation $quotation, QuotationStatus $status): SalesQuotation
    {
        return DB::transaction(function () use ($quotation, $status) {
            $quotation = SalesQuotation::query()->whereKey($quotation->id)->lockForUpdate()->firstOrFail();

            if (! $quotation->status->canTransitionTo($status)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot change status from {$quotation->status->label()} to {$status->label()}.",
                ]);
            }

            $quotation->update(['status' => $status]);

            return $quotation->fresh(['items']);
        });
    }

    public function convertToOrder(SalesQuotation $quotation, ?int $userId = null)
    {
        return DB::transaction(function () use ($quotation, $userId) {
            $quotation = SalesQuotation::query()->whereKey($quotation->id)->lockForUpdate()->with('items')->firstOrFail();

            if (! $quotation->status->canConvertToOrder()) {
                throw ValidationException::withMessages([
                    'status' => "Cannot convert a {$quotation->status->label()} quotation.",
                ]);
            }

            if ($quotation->sales_order_id) {
                throw ValidationException::withMessages([
                    'sales_order_id' => 'This quotation was already converted to an order.',
                ]);
            }

            if ($quotation->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Quotation has no line items.',
                ]);
            }

            $order = $this->orders->create([
                'customer_id' => $quotation->customer_id,
                'customer_name' => $quotation->customer_name,
                'customer_email' => $quotation->customer_email,
                'customer_phone' => $quotation->customer_phone,
                'customer_group_id' => $quotation->customer_group_id,
                'warehouse_id' => $quotation->warehouse_id,
                'notes' => $quotation->notes,
                'status' => SalesOrderStatus::Pending->value,
                'items' => $quotation->items->map(fn (SalesQuotationItem $item) => [
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => (float) $item->quantity,
                ])->all(),
            ], $userId);

            $quotation->update([
                'status' => QuotationStatus::Accepted,
                'sales_order_id' => $order->id,
            ]);

            Log::info('sales.quotation.converted', [
                'quotation_id' => $quotation->id,
                'order_id' => $order->id,
            ]);

            return $order;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function formatForDetail(SalesQuotation $quotation): array
    {
        $quotation->loadMissing('items');

        return [
            ...$this->formatForList($quotation),
            'customer_id' => $quotation->customer_id,
            'customer_email' => $quotation->customer_email,
            'customer_phone' => $quotation->customer_phone,
            'customer_group_id' => $quotation->customer_group_id,
            'warehouse_id' => $quotation->warehouse_id,
            'notes' => $quotation->notes,
            'valid_until' => $quotation->valid_until?->toDateString(),
            'sales_order_id' => $quotation->sales_order_id,
            'can_edit' => in_array($quotation->status, [QuotationStatus::Draft, QuotationStatus::Sent], true),
            'can_convert' => $quotation->status->canConvertToOrder() && ! $quotation->sales_order_id,
            'can_mark_sent' => $quotation->status->canTransitionTo(QuotationStatus::Sent),
            'can_mark_accepted' => $quotation->status->canTransitionTo(QuotationStatus::Accepted),
            'can_mark_expired' => $quotation->status->canTransitionTo(QuotationStatus::Expired),
            'items' => $quotation->items->map(fn (SalesQuotationItem $item) => [
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
     * @return array<string, mixed>
     */
    public function formatForList(SalesQuotation $quotation): array
    {
        return [
            'id' => $quotation->id,
            'number' => $quotation->number,
            'status' => $quotation->status->value,
            'status_label' => $quotation->status->label(),
            'customer_name' => $quotation->customer_name,
            'customer_id' => $quotation->customer_id,
            'currency' => $quotation->currency,
            'subtotal' => (string) $quotation->subtotal,
            'grand_total' => (string) $quotation->grand_total,
            'valid_until' => $quotation->valid_until?->toDateString(),
            'items_count' => $quotation->items_count ?? $quotation->items()->count(),
            'sales_order_id' => $quotation->sales_order_id,
            'created_at' => $quotation->created_at?->toIso8601String(),
        ];
    }

    private function expirePastDue(): void
    {
        SalesQuotation::query()
            ->whereIn('status', [QuotationStatus::Draft->value, QuotationStatus::Sent->value])
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', now()->toDateString())
            ->update(['status' => QuotationStatus::Expired->value]);
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
        $seq = SalesQuotation::query()->lockForUpdate()->count() + 1;

        return 'SQ-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
