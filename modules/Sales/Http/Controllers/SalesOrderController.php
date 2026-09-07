<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Sales\Enums\SalesDeliveryStatus;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Http\Requests\RecordSalesOrderPaymentRequest;
use Modules\Sales\Http\Requests\StoreSalesOrderRequest;
use Modules\Sales\Http\Requests\UpdateSalesOrderDeliveryStatusRequest;
use Modules\Sales\Http\Requests\UpdateSalesOrderLineDeliveryRequest;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Services\SalesOrderService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesOrderController extends Controller
{
    public function index(Request $request, SalesOrderService $service): InertiaResponse
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();
        [$dateFrom, $dateTo] = $this->dateRange($request);

        $status = $this->resolveStatus($request);

        return Inertia::render('Sales/Orders/Index', [
            'orders' => $service->listPaginated(
                search: $search ?: null,
                status: $status,
                perPage: $perPage,
                dateFrom: $dateFrom,
                dateTo: $dateTo,
            ),
            'filters' => [
                'search' => $search,
                'status' => $status?->value,
                'date_from' => $dateFrom ?? '',
                'date_to' => $dateTo ?? '',
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'statusOptions' => collect(SalesOrderStatus::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function export(Request $request, SalesOrderService $service): StreamedResponse|Response
    {
        $request->validate([
            'format' => ['required', Rule::in(['csv', 'pdf', 'print'])],
        ]);

        $search = $request->string('search')->trim()->toString();
        [$dateFrom, $dateTo] = $this->dateRange($request);
        $status = $this->resolveStatus($request);

        $rows = $service->listForExport(
            search: $search ?: null,
            status: $status,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );

        $title = 'Sales Orders';
        $generatedAt = Date::now()->timezone(config('app.timezone'))->format('Y-m-d H:i');
        $filterSummary = array_values(array_filter([
            $status ? 'Status: '.$status->label() : null,
            $search !== '' ? 'Search: '.$search : null,
            $dateFrom ? 'From: '.$dateFrom : null,
            $dateTo ? 'To: '.$dateTo : null,
        ]));

        if ($request->string('format')->toString() === 'csv') {
            return $this->csvDownload($rows, $title);
        }

        return response()->view('sales::orders-export', [
            'title' => $title,
            'rows' => $rows,
            'filters' => $filterSummary,
            'generatedAt' => $generatedAt,
            'autoprint' => true,
        ]);
    }

    public function create(Request $request, SalesOrderService $service): InertiaResponse
    {
        $customers = $service->customerOptions();
        $prefillCustomerId = $request->integer('customer_id') ?: null;
        $prefillCustomer = null;

        if ($prefillCustomerId) {
            $prefillCustomer = collect($customers)->firstWhere('id', $prefillCustomerId);
        }

        return Inertia::render('Sales/Orders/Create', [
            'productOptions' => $service->searchProducts(),
            'customers' => $customers,
            'customerGroups' => $service->customerGroups(),
            'warehouses' => $service->warehouses(),
            'prefillCustomer' => $prefillCustomer,
        ]);
    }

    public function store(StoreSalesOrderRequest $request, SalesOrderService $service): RedirectResponse
    {
        $order = $service->create($request->validated(), $request->user()->id);

        return redirect()
            ->route('sales.orders.show', $order)
            ->with('success', 'Sales order created.');
    }

    public function show(SalesOrder $order, SalesOrderService $service): InertiaResponse
    {
        return Inertia::render('Sales/Orders/Show', [
            'order' => $service->formatForDetail($order),
        ]);
    }

    public function confirm(SalesOrder $order, SalesOrderService $service): RedirectResponse
    {
        $service->confirm($order, request()->user()->id);

        return back()->with('success', 'Order confirmed. Stock fulfilled.');
    }

    public function cancel(SalesOrder $order, SalesOrderService $service): RedirectResponse
    {
        $service->cancel($order, request()->user()->id);

        return back()->with('success', 'Order cancelled.');
    }

    public function updateDeliveryStatus(
        UpdateSalesOrderDeliveryStatusRequest $request,
        SalesOrder $order,
        SalesOrderService $service,
    ): RedirectResponse {
        $service->updateDeliveryStatus(
            $order,
            SalesDeliveryStatus::from($request->validated('delivery_status')),
            $request->user()->id,
            $request->validated('note'),
        );

        return back()->with('success', 'Delivery status updated.');
    }

    public function updateLineDelivery(
        UpdateSalesOrderLineDeliveryRequest $request,
        SalesOrder $order,
        SalesOrderItem $item,
        SalesOrderService $service,
    ): RedirectResponse {
        $service->updateLineDelivery(
            $order,
            $item,
            SalesDeliveryStatus::from($request->validated('delivery_status')),
            $request->filled('quantity_delivered') ? (float) $request->validated('quantity_delivered') : null,
            $request->user()->id,
            $request->validated('note'),
        );

        return back()->with('success', 'Line delivery updated.');
    }

    public function recordPayment(
        RecordSalesOrderPaymentRequest $request,
        SalesOrder $order,
        SalesOrderService $service,
    ): RedirectResponse {
        $service->recordPayment($order, $request->validated(), $request->user()->id);

        return back()->with('success', 'Payment recorded.');
    }

    public function previewPrice(Request $request, SalesOrderService $service): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'customer_group_id' => ['nullable', 'integer', 'exists:customer_groups,id'],
        ]);

        return response()->json($service->previewPrice(
            productId: (int) $request->input('product_id'),
            quantity: (int) $request->input('quantity', 1),
            productVariantId: $request->filled('product_variant_id') ? (int) $request->input('product_variant_id') : null,
            customerGroupId: $request->filled('customer_group_id') ? (int) $request->input('customer_group_id') : null,
        ));
    }

    public function productVariants(int $productId, SalesOrderService $service): JsonResponse
    {
        return response()->json($service->variantsForProduct($productId));
    }

    private function resolveStatus(Request $request): ?SalesOrderStatus
    {
        if (! $request->filled('status')) {
            return null;
        }

        return SalesOrderStatus::tryFrom($request->string('status')->toString());
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function dateRange(Request $request): array
    {
        $from = $this->parseDate($request->string('date_from')->toString());
        $to = $this->parseDate($request->string('date_to')->toString());

        if ($from && $to && $from > $to) {
            return [$to, $from];
        }

        return [$from, $to];
    }

    private function parseDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function csvDownload($rows, string $title): StreamedResponse
    {
        $filename = strtolower(str_replace(' ', '-', $title)).'-'.Date::now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Number',
                'Customer',
                'Status',
                'Delivery',
                'Payment',
                'Items',
                'Currency',
                'Grand total',
                'Paid',
                'Due',
                'Created',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['number'],
                    $row['customer_name'],
                    $row['status'],
                    $row['delivery_status'],
                    $row['payment_status'],
                    $row['items_count'],
                    $row['currency'],
                    $row['grand_total'],
                    $row['amount_paid'],
                    $row['amount_due'],
                    $row['created_at'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
