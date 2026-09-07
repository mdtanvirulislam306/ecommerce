<?php

namespace Modules\Sales\Services;

use App\Core\Contracts\PriceResolver;
use App\Core\Contracts\StockAvailability;
use App\Core\Events\SalesOrderCancelled;
use App\Core\Events\SalesOrderConfirmed;
use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Crm\Services\CustomerService;
use Modules\Sales\Enums\PaymentMethod;
use Modules\Sales\Enums\SalesDeliveryStatus;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Enums\SalesOrderStatusField;
use Modules\Sales\Enums\SalesPaymentStatus;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Models\SalesOrderStatusLog;

class SalesOrderService extends Service
{
    public function __construct(
        private readonly PriceResolver $prices,
        private readonly StockAvailability $stock,
        private readonly CustomerService $customers,
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
        ?string $dateFrom = null,
        ?string $dateTo = null,
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
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (SalesOrder $order) => $this->formatForList($order));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listForExport(
        ?string $search = null,
        ?SalesOrderStatus $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $limit = 5000,
    ): Collection {
        return SalesOrder::query()
            ->withCount('items')
            ->when($status, fn ($query, $status) => $query->where('status', $status->value))
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            }))
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (SalesOrder $order) => [
                'number' => $order->number,
                'customer_name' => $order->customer_name,
                'status' => $order->status->label(),
                'delivery_status' => $order->delivery_status->label(),
                'payment_status' => $order->payment_status->label(),
                'items_count' => $order->items_count ?? $order->items()->count(),
                'currency' => $order->currency,
                'grand_total' => (string) $order->grand_total,
                'amount_paid' => (string) $order->amount_paid,
                'amount_due' => (string) $order->amount_due,
                'created_at' => $order->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            ]);
    }

    /**
     * @param  array{
     *     customer_id?: int|null,
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
            $data = $this->customers->applySnapshot($data);

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
                'delivery_status' => SalesDeliveryStatus::Pending,
                'payment_status' => SalesPaymentStatus::Unpaid,
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
                'amount_paid' => 0,
                'amount_due' => $totals['subtotal'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $this->logActivity(
                order: $order,
                field: SalesOrderStatusField::Order,
                from: null,
                to: $order->status->value,
                userId: $userId,
                note: 'Order created',
            );
            $this->logActivity(
                order: $order,
                field: SalesOrderStatusField::Delivery,
                from: null,
                to: SalesDeliveryStatus::Pending->value,
                userId: $userId,
                note: 'Initial delivery status',
            );
            $this->logActivity(
                order: $order,
                field: SalesOrderStatusField::Payment,
                from: null,
                to: SalesPaymentStatus::Unpaid->value,
                userId: $userId,
                note: 'Initial payment status',
            );

            foreach ($lines as $index => $line) {
                $order->items()->create([
                    ...$line,
                    'delivery_status' => SalesDeliveryStatus::Pending,
                    'quantity_delivered' => 0,
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

                $fromStatus = $order->status;

                $order->update([
                    'status' => SalesOrderStatus::Confirmed,
                    'confirmed_at' => now(),
                ]);

                $this->logActivity(
                    order: $order,
                    field: SalesOrderStatusField::Order,
                    from: $fromStatus->value,
                    to: SalesOrderStatus::Confirmed->value,
                    userId: $userId,
                    note: 'Order confirmed; stock fulfilled',
                );

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

                $fromStatus = $order->status;
                $fromDelivery = $order->delivery_status;

                $order->update([
                    'status' => SalesOrderStatus::Cancelled,
                    'delivery_status' => SalesDeliveryStatus::Cancelled,
                    'cancelled_at' => now(),
                ]);

                $this->logActivity(
                    order: $order,
                    field: SalesOrderStatusField::Order,
                    from: $fromStatus->value,
                    to: SalesOrderStatus::Cancelled->value,
                    userId: $userId,
                    note: $wasConfirmed ? 'Order cancelled; stock restocked' : 'Order cancelled',
                );

                if ($fromDelivery !== SalesDeliveryStatus::Cancelled) {
                    $this->logActivity(
                        order: $order,
                        field: SalesOrderStatusField::Delivery,
                        from: $fromDelivery->value,
                        to: SalesDeliveryStatus::Cancelled->value,
                        userId: $userId,
                        note: 'Delivery cancelled with order',
                    );
                }

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

    public function updateDeliveryStatus(
        SalesOrder $order,
        SalesDeliveryStatus $status,
        ?int $userId = null,
        ?string $note = null,
    ): SalesOrder {
        return DB::transaction(function () use ($order, $status, $userId, $note) {
            $order = SalesOrder::query()->whereKey($order->id)->lockForUpdate()->with('items')->firstOrFail();

            if ($order->status === SalesOrderStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'delivery_status' => 'Cannot update delivery on a cancelled order.',
                ]);
            }

            if ($status === SalesDeliveryStatus::Partial) {
                throw ValidationException::withMessages([
                    'delivery_status' => 'Partial is set automatically when some lines are delivered.',
                ]);
            }

            if (! $order->delivery_status->canTransitionTo($status) && $order->delivery_status !== $status) {
                throw ValidationException::withMessages([
                    'delivery_status' => "Cannot move delivery from {$order->delivery_status->label()} to {$status->label()}.",
                ]);
            }

            $from = $order->delivery_status;

            foreach ($order->items as $item) {
                if ($item->delivery_status === SalesDeliveryStatus::Cancelled) {
                    continue;
                }

                $qtyDelivered = $status === SalesDeliveryStatus::Delivered
                    ? (float) $item->quantity
                    : (float) $item->quantity_delivered;

                $item->update([
                    'delivery_status' => $status,
                    'quantity_delivered' => number_format($qtyDelivered, 4, '.', ''),
                ]);
            }

            $order->update(['delivery_status' => $status]);

            $this->logActivity(
                order: $order,
                field: SalesOrderStatusField::Delivery,
                from: $from->value,
                to: $status->value,
                userId: $userId,
                note: $note ?? 'All lines updated to '.$status->label(),
            );

            return $order->fresh(['items']);
        });
    }

    public function updateLineDelivery(
        SalesOrder $order,
        SalesOrderItem $item,
        SalesDeliveryStatus $status,
        ?float $quantityDelivered = null,
        ?int $userId = null,
        ?string $note = null,
    ): SalesOrder {
        return DB::transaction(function () use ($order, $item, $status, $quantityDelivered, $userId, $note) {
            $order = SalesOrder::query()->whereKey($order->id)->lockForUpdate()->with('items')->firstOrFail();

            if ($item->sales_order_id !== $order->id) {
                throw ValidationException::withMessages([
                    'item' => 'Line does not belong to this order.',
                ]);
            }

            if ($order->status === SalesOrderStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'delivery_status' => 'Cannot update delivery on a cancelled order.',
                ]);
            }

            if (! $status->isLineApplicable()) {
                throw ValidationException::withMessages([
                    'delivery_status' => 'Partial cannot be set on a single line.',
                ]);
            }

            $item = $order->items->firstWhere('id', $item->id) ?? $item;
            $from = $item->delivery_status;
            $qty = $quantityDelivered;

            if ($qty === null) {
                $qty = $status === SalesDeliveryStatus::Delivered
                    ? (float) $item->quantity
                    : (float) $item->quantity_delivered;
            }

            if ($qty > ((float) $item->quantity) + 0.0001) {
                throw ValidationException::withMessages([
                    'quantity_delivered' => 'Delivered quantity cannot exceed ordered quantity.',
                ]);
            }

            // Partial qty on a "delivered" request → shipped until fully delivered
            if ($status === SalesDeliveryStatus::Delivered && $qty + 0.0001 < (float) $item->quantity) {
                $status = SalesDeliveryStatus::Shipped;
            }

            $item->update([
                'delivery_status' => $status,
                'quantity_delivered' => number_format($qty, 4, '.', ''),
            ]);

            $this->logActivity(
                order: $order,
                field: SalesOrderStatusField::LineDelivery,
                from: $from->value,
                to: $status->value,
                userId: $userId,
                note: $note ?? sprintf(
                    '%s: %s → %s (delivered %s/%s)',
                    $item->name,
                    $from->label(),
                    $status->label(),
                    number_format($qty, 2, '.', ''),
                    number_format((float) $item->quantity, 2, '.', ''),
                ),
            );

            $orderDeliveryFrom = $order->delivery_status;
            $derived = $this->deriveOrderDeliveryStatus($order->fresh(['items'])->items);
            $order->update(['delivery_status' => $derived]);

            if ($orderDeliveryFrom !== $derived) {
                $this->logActivity(
                    order: $order,
                    field: SalesOrderStatusField::Delivery,
                    from: $orderDeliveryFrom->value,
                    to: $derived->value,
                    userId: $userId,
                    note: 'Recalculated from line deliveries',
                );
            }

            return $order->fresh(['items']);
        });
    }

    /**
     * Record a payment amount against the order (partial or full).
     *
     * @param  array{amount: float|int|string, method?: string|null, note?: string|null}  $data
     */
    public function recordPayment(SalesOrder $order, array $data, ?int $userId = null): SalesOrder
    {
        $amount = (float) $data['amount'];

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        $invoice = SalesInvoice::query()
            ->where('sales_order_id', $order->id)
            ->orderByDesc('id')
            ->first();

        if ($invoice) {
            if ($amount > ((float) $invoice->amount_due) + 0.0001) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment exceeds the remaining amount due on invoice '.$invoice->number.'.',
                ]);
            }

            app(PaymentService::class)->create([
                'sales_invoice_id' => $invoice->id,
                'amount' => $amount,
                'method' => $data['method'] ?? PaymentMethod::Cash->value,
                'notes' => $data['note'] ?? null,
            ], $userId);

            $this->logActivity(
                order: $order->id,
                field: SalesOrderStatusField::PaymentRecord,
                from: null,
                to: number_format($amount, 2, '.', ''),
                userId: $userId,
                note: sprintf(
                    'Received %s %s on invoice %s%s',
                    $invoice->currency,
                    number_format($amount, 2, '.', ''),
                    $invoice->number,
                    ! empty($data['note']) ? ' — '.$data['note'] : '',
                ),
            );

            return $order->fresh(['items']);
        }

        return DB::transaction(function () use ($order, $data, $amount, $userId) {
            $order = SalesOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            $due = (float) $order->amount_due;

            if ($amount > $due + 0.0001) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment exceeds the remaining amount due ('.$order->currency.' '.number_format($due, 2).').',
                ]);
            }

            $paid = (float) $order->amount_paid + $amount;
            $grand = (float) $order->grand_total;
            $newDue = max(0, $grand - $paid);
            $fromStatus = $order->payment_status;
            $next = $this->resolvePaymentStatus($paid, $newDue);

            $order->update([
                'amount_paid' => number_format($paid, 4, '.', ''),
                'amount_due' => number_format($newDue, 4, '.', ''),
                'payment_status' => $next,
            ]);

            $method = PaymentMethod::tryFrom($data['method'] ?? PaymentMethod::Cash->value) ?? PaymentMethod::Cash;

            $this->logActivity(
                order: $order,
                field: SalesOrderStatusField::PaymentRecord,
                from: null,
                to: number_format($amount, 2, '.', ''),
                userId: $userId,
                note: sprintf(
                    'Received %s %s via %s%s',
                    $order->currency,
                    number_format($amount, 2, '.', ''),
                    $method->label(),
                    ! empty($data['note']) ? ' — '.$data['note'] : '',
                ),
            );

            if ($fromStatus !== $next) {
                $this->logActivity(
                    order: $order,
                    field: SalesOrderStatusField::Payment,
                    from: $fromStatus->value,
                    to: $next->value,
                    userId: $userId,
                    note: sprintf('Paid %s / Due %s', number_format($paid, 2, '.', ''), number_format($newDue, 2, '.', '')),
                );
            }

            return $order->fresh(['items']);
        });
    }

    /**
     * @deprecated Use recordPayment()
     */
    public function updatePaymentStatus(
        SalesOrder $order,
        SalesPaymentStatus $status,
        ?int $userId = null,
        ?string $note = null,
        ?float $amount = null,
    ): SalesOrder {
        if (in_array($status, [SalesPaymentStatus::Partial, SalesPaymentStatus::Paid], true)) {
            if ($amount === null || $amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Enter how much was paid.',
                ]);
            }

            return $this->recordPayment($order, [
                'amount' => $amount,
                'note' => $note,
            ], $userId);
        }

        return DB::transaction(function () use ($order, $status, $userId, $note) {
            $order = SalesOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->payment_status === $status) {
                return $order;
            }

            $from = $order->payment_status;
            $payload = ['payment_status' => $status];

            if ($status === SalesPaymentStatus::Unpaid) {
                $payload['amount_paid'] = 0;
                $payload['amount_due'] = $order->grand_total;
            }

            if ($status === SalesPaymentStatus::Refunded) {
                $payload['amount_due'] = 0;
            }

            $order->update($payload);

            $this->logActivity(
                order: $order,
                field: SalesOrderStatusField::Payment,
                from: $from->value,
                to: $status->value,
                userId: $userId,
                note: $note,
            );

            return $order->fresh();
        });
    }

    /**
     * Keep order payment totals/status in sync when invoice balances change.
     */
    public function syncPaymentStatusFromInvoice(SalesInvoice $invoice, ?int $userId = null): void
    {
        if (! $invoice->sales_order_id) {
            return;
        }

        $order = SalesOrder::query()->whereKey($invoice->sales_order_id)->lockForUpdate()->first();

        if ($order === null) {
            return;
        }

        $paid = (float) $invoice->amount_paid;
        $due = (float) $invoice->amount_due;
        $credited = (float) $invoice->creditNotes()->sum('amount');

        $next = match (true) {
            $paid <= 0.0001 && $credited > 0 && $due <= 0.0001 => SalesPaymentStatus::Refunded,
            $due <= 0.0001 && $paid > 0 => SalesPaymentStatus::Paid,
            $paid > 0 && $due > 0 => SalesPaymentStatus::Partial,
            default => SalesPaymentStatus::Unpaid,
        };

        $fromStatus = $order->payment_status;
        $fromPaid = (float) $order->amount_paid;

        $order->update([
            'amount_paid' => number_format($paid, 4, '.', ''),
            'amount_due' => number_format($due, 4, '.', ''),
            'payment_status' => $next,
        ]);

        if (abs($fromPaid - $paid) > 0.0001) {
            $this->logActivity(
                order: $order,
                field: SalesOrderStatusField::PaymentRecord,
                from: number_format($fromPaid, 2, '.', ''),
                to: number_format($paid, 2, '.', ''),
                userId: $userId,
                note: 'Payment totals synced from invoice '.$invoice->number,
            );
        }

        if ($fromStatus !== $next) {
            $this->logActivity(
                order: $order,
                field: SalesOrderStatusField::Payment,
                from: $fromStatus->value,
                to: $next->value,
                userId: $userId,
                note: 'Synced from invoice '.$invoice->number,
            );
        }
    }

    public function logActivity(
        SalesOrder|int $order,
        SalesOrderStatusField $field,
        ?string $from,
        string $to,
        ?int $userId = null,
        ?string $note = null,
    ): void {
        $orderId = $order instanceof SalesOrder ? $order->id : $order;

        SalesOrderStatusLog::query()->create([
            'sales_order_id' => $orderId,
            'field' => $field,
            'from_value' => $from,
            'to_value' => $to,
            'note' => $note,
            'changed_by' => $userId,
            'created_at' => now(),
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function deliveryStatusOptions(): array
    {
        return collect(SalesDeliveryStatus::cases())->map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ])->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function paymentStatusOptions(): array
    {
        return collect(SalesPaymentStatus::cases())->map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ])->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function formatForDetail(SalesOrder $order): array
    {
        $order->loadMissing(['items', 'statusLogs.changedByUser:id,name', 'invoices:id,sales_order_id,number']);

        $invoice = $order->invoices->first();
        $lineOptions = collect(SalesDeliveryStatus::cases())
            ->filter(fn (SalesDeliveryStatus $status) => $status->isLineApplicable())
            ->map(fn (SalesDeliveryStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->values()
            ->all();

        return [
            ...$this->formatForList($order),
            'customer_id' => $order->customer_id,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'customer_group_id' => $order->customer_group_id,
            'warehouse_id' => $order->warehouse_id,
            'notes' => $order->notes,
            'confirmed_at' => $order->confirmed_at?->toIso8601String(),
            'cancelled_at' => $order->cancelled_at?->toIso8601String(),
            'can_confirm' => $order->status->canTransitionTo(SalesOrderStatus::Confirmed),
            'can_cancel' => $order->status->canTransitionTo(SalesOrderStatus::Cancelled),
            'can_record_payment' => $order->status !== SalesOrderStatus::Cancelled
                && (float) $order->amount_due > 0
                && $order->payment_status !== SalesPaymentStatus::Refunded,
            'can_update_delivery' => $order->status !== SalesOrderStatus::Cancelled
                && $order->delivery_status !== SalesDeliveryStatus::Delivered
                && $order->delivery_status !== SalesDeliveryStatus::Cancelled,
            'delivery_next' => collect(SalesDeliveryStatus::cases())
                ->filter(fn (SalesDeliveryStatus $status) => $status !== SalesDeliveryStatus::Partial
                    && $order->delivery_status->canTransitionTo($status))
                ->map(fn (SalesDeliveryStatus $status) => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->values()
                ->all(),
            'line_delivery_options' => $lineOptions,
            'invoice_id' => $invoice?->id,
            'invoice_number' => $invoice?->number,
            'items' => $order->items->map(fn (SalesOrderItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'sku' => $item->sku,
                'name' => $item->name,
                'quantity' => (string) $item->quantity,
                'quantity_delivered' => (string) $item->quantity_delivered,
                'delivery_status' => $item->delivery_status->value,
                'delivery_status_label' => $item->delivery_status->label(),
                'unit_price' => (string) $item->unit_price,
                'line_total' => (string) $item->line_total,
                'currency' => $item->currency,
            ])->all(),
            'status_logs' => $order->statusLogs->map(fn (SalesOrderStatusLog $log) => $this->formatStatusLog($log))->all(),
            'payment_methods' => collect(PaymentMethod::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
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
    /**
     * @return list<array{id: int, name: string, code: string, email: ?string, phone: ?string, company: ?string, customer_group_id: ?int}>
     */
    public function customerOptions(): array
    {
        return $this->customers->optionList();
    }

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
            'delivery_status' => $order->delivery_status->value,
            'delivery_status_label' => $order->delivery_status->label(),
            'payment_status' => $order->payment_status->value,
            'payment_status_label' => $order->payment_status->label(),
            'customer_name' => $order->customer_name,
            'customer_id' => $order->customer_id,
            'currency' => $order->currency,
            'subtotal' => (string) $order->subtotal,
            'grand_total' => (string) $order->grand_total,
            'amount_paid' => (string) $order->amount_paid,
            'amount_due' => (string) $order->amount_due,
            'items_count' => $order->items_count ?? $order->items()->count(),
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  Collection<int, SalesOrderItem>  $items
     */
    private function deriveOrderDeliveryStatus($items): SalesDeliveryStatus
    {
        if ($items->isEmpty()) {
            return SalesDeliveryStatus::Pending;
        }

        if ($items->every(fn (SalesOrderItem $item) => $item->delivery_status === SalesDeliveryStatus::Cancelled)) {
            return SalesDeliveryStatus::Cancelled;
        }

        $active = $items->filter(
            fn (SalesOrderItem $item) => $item->delivery_status !== SalesDeliveryStatus::Cancelled
        );

        if ($active->every(fn (SalesOrderItem $item) => $item->delivery_status === SalesDeliveryStatus::Delivered)) {
            return SalesDeliveryStatus::Delivered;
        }

        if ($active->every(fn (SalesOrderItem $item) => $item->delivery_status === SalesDeliveryStatus::Pending)) {
            return SalesDeliveryStatus::Pending;
        }

        if ($active->every(fn (SalesOrderItem $item) => in_array($item->delivery_status, [
            SalesDeliveryStatus::Shipped,
            SalesDeliveryStatus::Delivered,
        ], true))) {
            return SalesDeliveryStatus::Shipped;
        }

        $hasProgress = $active->contains(fn (SalesOrderItem $item) => in_array($item->delivery_status, [
            SalesDeliveryStatus::Processing,
            SalesDeliveryStatus::Shipped,
            SalesDeliveryStatus::Delivered,
        ], true));

        if ($hasProgress && ! $active->every(fn (SalesOrderItem $item) => $item->delivery_status === SalesDeliveryStatus::Delivered)) {
            return SalesDeliveryStatus::Partial;
        }

        return SalesDeliveryStatus::Processing;
    }

    private function resolvePaymentStatus(float $paid, float $due): SalesPaymentStatus
    {
        if ($due <= 0.0001 && $paid > 0) {
            return SalesPaymentStatus::Paid;
        }

        if ($paid > 0 && $due > 0) {
            return SalesPaymentStatus::Partial;
        }

        return SalesPaymentStatus::Unpaid;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatStatusLog(SalesOrderStatusLog $log): array
    {
        return [
            'id' => $log->id,
            'field' => $log->field->value,
            'field_label' => $log->field->label(),
            'from_value' => $log->from_value,
            'from_label' => $this->statusValueLabel($log->field, $log->from_value),
            'to_value' => $log->to_value,
            'to_label' => $this->statusValueLabel($log->field, $log->to_value),
            'note' => $log->note,
            'changed_by' => $log->changedByUser?->name,
            'created_at' => $log->created_at?->toIso8601String(),
        ];
    }

    private function statusValueLabel(SalesOrderStatusField $field, ?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($field) {
            SalesOrderStatusField::Order => SalesOrderStatus::tryFrom($value)?->label() ?? $value,
            SalesOrderStatusField::Delivery, SalesOrderStatusField::LineDelivery => SalesDeliveryStatus::tryFrom($value)?->label() ?? $value,
            SalesOrderStatusField::Payment => SalesPaymentStatus::tryFrom($value)?->label() ?? $value,
            default => $value,
        };
    }
}
