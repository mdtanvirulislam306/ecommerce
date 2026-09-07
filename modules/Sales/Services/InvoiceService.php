<?php

namespace Modules\Sales\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Sales\Enums\InvoiceStatus;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesInvoiceItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;

class InvoiceService extends Service
{
    public function __construct(
        private readonly SalesOrderService $orders,
    ) {}

    public function listPaginated(
        ?string $search = null,
        ?InvoiceStatus $status = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        $this->refreshOverdueStatuses();

        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return SalesInvoice::query()
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
            ->through(fn (SalesInvoice $invoice) => $this->formatForList($invoice));
    }

    /**
     * @return list<array{id: int, number: string, customer_name: string, grand_total: string}>
     */
    public function confirmedOrdersWithoutInvoice(): array
    {
        $invoicedOrderIds = SalesInvoice::query()
            ->whereNotNull('sales_order_id')
            ->pluck('sales_order_id')
            ->all();

        return SalesOrder::query()
            ->where('status', SalesOrderStatus::Confirmed->value)
            ->when($invoicedOrderIds !== [], fn ($q) => $q->whereNotIn('id', $invoicedOrderIds))
            ->orderByDesc('confirmed_at')
            ->limit(100)
            ->get(['id', 'number', 'customer_name', 'grand_total', 'currency'])
            ->map(fn (SalesOrder $order) => [
                'id' => $order->id,
                'number' => $order->number,
                'customer_name' => $order->customer_name,
                'grand_total' => (string) $order->grand_total,
                'currency' => $order->currency,
            ])
            ->all();
    }

    /**
     * @param  array{sales_order_id: int, due_date?: string|null, notes?: string|null}  $data
     */
    public function createFromOrder(array $data, ?int $userId = null): SalesInvoice
    {
        return DB::transaction(function () use ($data, $userId) {
            $order = SalesOrder::query()
                ->whereKey((int) $data['sales_order_id'])
                ->lockForUpdate()
                ->with('items')
                ->firstOrFail();

            if ($order->status !== SalesOrderStatus::Confirmed) {
                throw ValidationException::withMessages([
                    'sales_order_id' => 'Only confirmed sales orders can be invoiced.',
                ]);
            }

            $existing = SalesInvoice::query()->where('sales_order_id', $order->id)->exists();

            if ($existing) {
                throw ValidationException::withMessages([
                    'sales_order_id' => 'An invoice already exists for this order.',
                ]);
            }

            if ($order->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'sales_order_id' => 'Order has no line items.',
                ]);
            }

            $grandTotal = (float) $order->grand_total;

            $invoice = SalesInvoice::query()->create([
                'number' => $this->nextNumber(),
                'sales_order_id' => $order->id,
                'status' => InvoiceStatus::Due,
                'customer_id' => $order->customer_id,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'customer_phone' => $order->customer_phone,
                'currency' => $order->currency,
                'subtotal' => $order->subtotal,
                'tax_total' => $order->tax_total,
                'grand_total' => $order->grand_total,
                'amount_paid' => 0,
                'amount_due' => number_format($grandTotal, 4, '.', ''),
                'due_date' => $data['due_date'] ?? now()->addDays(14)->toDateString(),
                'notes' => $data['notes'] ?? $order->notes,
                'created_by' => $userId,
            ]);

            foreach ($order->items as $index => $item) {
                /** @var SalesOrderItem $item */
                $invoice->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                    'currency' => $item->currency,
                    'sort_order' => $index,
                ]);
            }

            Log::info('sales.invoice.created', [
                'invoice_id' => $invoice->id,
                'order_id' => $order->id,
            ]);

            return $invoice->fresh(['items']);
        });
    }

    public function recalculateBalances(SalesInvoice $invoice): SalesInvoice
    {
        $invoice = SalesInvoice::query()->whereKey($invoice->id)->lockForUpdate()->with('creditNotes')->firstOrFail();

        $paid = (float) $invoice->payments()->sum('amount');
        $credited = (float) $invoice->creditNotes()->sum('amount');
        $grand = (float) $invoice->grand_total;
        $due = max(0, $grand - $paid - $credited);

        $status = $this->resolveStatus($paid, $due, $grand, $invoice->due_date?->toDateString());

        $invoice->update([
            'amount_paid' => number_format($paid, 4, '.', ''),
            'amount_due' => number_format($due, 4, '.', ''),
            'status' => $status,
        ]);

        $invoice = $invoice->fresh(['items', 'payments', 'creditNotes']);

        $this->orders->syncPaymentStatusFromInvoice($invoice);

        return $invoice;
    }

    /**
     * @return array<string, mixed>
     */
    public function formatForDetail(SalesInvoice $invoice): array
    {
        $invoice->loadMissing(['items', 'payments', 'creditNotes']);

        return [
            ...$this->formatForList($invoice),
            'customer_email' => $invoice->customer_email,
            'customer_phone' => $invoice->customer_phone,
            'sales_order_id' => $invoice->sales_order_id,
            'notes' => $invoice->notes,
            'due_date' => $invoice->due_date?->toDateString(),
            'items' => $invoice->items->map(fn (SalesInvoiceItem $item) => [
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
    public function formatForList(SalesInvoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'status' => $invoice->status->value,
            'status_label' => $invoice->status->label(),
            'customer_name' => $invoice->customer_name,
            'currency' => $invoice->currency,
            'grand_total' => (string) $invoice->grand_total,
            'amount_paid' => (string) $invoice->amount_paid,
            'amount_due' => (string) $invoice->amount_due,
            'due_date' => $invoice->due_date?->toDateString(),
            'sales_order_id' => $invoice->sales_order_id,
            'items_count' => $invoice->items_count ?? $invoice->items()->count(),
            'created_at' => $invoice->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array{id: int, number: string, customer_name: string, amount_due: string, currency: string}>
     */
    public function openInvoicesForSelect(): array
    {
        $this->refreshOverdueStatuses();

        return SalesInvoice::query()
            ->whereIn('status', [
                InvoiceStatus::Due->value,
                InvoiceStatus::Partial->value,
                InvoiceStatus::Overdue->value,
            ])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['id', 'number', 'customer_name', 'amount_due', 'currency'])
            ->map(fn (SalesInvoice $invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'customer_name' => $invoice->customer_name,
                'amount_due' => (string) $invoice->amount_due,
                'currency' => $invoice->currency,
            ])
            ->all();
    }

    private function resolveStatus(float $paid, float $due, float $grand, ?string $dueDate): InvoiceStatus
    {
        if ($due <= 0.0001 || $paid >= $grand) {
            return InvoiceStatus::Paid;
        }

        if ($paid > 0) {
            return InvoiceStatus::Partial;
        }

        if ($dueDate && $dueDate < now()->toDateString()) {
            return InvoiceStatus::Overdue;
        }

        return InvoiceStatus::Due;
    }

    private function refreshOverdueStatuses(): void
    {
        SalesInvoice::query()
            ->whereIn('status', [InvoiceStatus::Due->value, InvoiceStatus::Partial->value])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->where('amount_due', '>', 0)
            ->where('amount_paid', '=', 0)
            ->update(['status' => InvoiceStatus::Overdue->value]);

        SalesInvoice::query()
            ->where('status', InvoiceStatus::Due->value)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->where('amount_due', '>', 0)
            ->where('amount_paid', '>', 0)
            ->update(['status' => InvoiceStatus::Partial->value]);
    }

    private function nextNumber(): string
    {
        $seq = SalesInvoice::query()->lockForUpdate()->count() + 1;

        return 'INV-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
