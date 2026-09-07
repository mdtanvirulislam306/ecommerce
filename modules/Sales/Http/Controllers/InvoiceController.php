<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Sales\Enums\InvoiceStatus;
use Modules\Sales\Enums\SalesOrderStatusField;
use Modules\Sales\Http\Requests\StoreInvoiceRequest;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Services\InvoiceService;
use Modules\Sales\Services\SalesOrderService;

class InvoiceController extends Controller
{
    public function index(Request $request, InvoiceService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();
        $status = $this->resolveStatus($request);

        return Inertia::render('Sales/Invoices/Index', [
            'invoices' => $service->listPaginated($search ?: null, $status, $perPage),
            'filters' => [
                'search' => $search,
                'status' => $status?->value,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'statusOptions' => collect(InvoiceStatus::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function create(InvoiceService $service): Response
    {
        return Inertia::render('Sales/Invoices/Create', [
            'orders' => $service->confirmedOrdersWithoutInvoice(),
        ]);
    }

    public function store(StoreInvoiceRequest $request, InvoiceService $service, SalesOrderService $orders): RedirectResponse
    {
        $invoice = $service->createFromOrder($request->validated(), $request->user()->id);

        if ($invoice->sales_order_id) {
            $orders->logActivity(
                order: $invoice->sales_order_id,
                field: SalesOrderStatusField::Invoice,
                from: null,
                to: $invoice->number,
                userId: $request->user()->id,
                note: 'Invoice '.$invoice->number.' created',
            );
        }

        return redirect()
            ->route('sales.invoices.show', $invoice)
            ->with('success', 'Invoice created from sales order.');
    }

    public function show(SalesInvoice $invoice, InvoiceService $service): Response
    {
        return Inertia::render('Sales/Invoices/Show', [
            'invoice' => $service->formatForDetail($invoice),
        ]);
    }

    public function print(SalesInvoice $invoice, InvoiceService $service, SalesOrderService $orders): View
    {
        $payload = $service->formatForDetail($invoice);

        if ($invoice->sales_order_id) {
            $orders->logActivity(
                order: $invoice->sales_order_id,
                field: SalesOrderStatusField::Invoice,
                from: null,
                to: 'printed',
                userId: request()->user()?->id,
                note: 'Invoice '.$invoice->number.' printed',
            );
        }

        return view('sales::invoice-print', [
            'invoice' => $payload,
            'autoprint' => request()->boolean('autoprint', true),
        ]);
    }

    private function resolveStatus(Request $request): ?InvoiceStatus
    {
        if (! $request->filled('status')) {
            return null;
        }

        return InvoiceStatus::tryFrom($request->string('status')->toString());
    }
}
