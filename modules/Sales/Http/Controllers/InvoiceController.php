<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Sales\Enums\InvoiceStatus;
use Modules\Sales\Http\Requests\StoreInvoiceRequest;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Services\InvoiceService;

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
            'listTitle' => $this->resolveTitle($request),
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

    public function store(StoreInvoiceRequest $request, InvoiceService $service): RedirectResponse
    {
        $invoice = $service->createFromOrder($request->validated(), $request->user()->id);

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

    private function resolveStatus(Request $request): ?InvoiceStatus
    {
        foreach ([
            'sales.invoices.paid' => InvoiceStatus::Paid,
            'sales.invoices.partial' => InvoiceStatus::Partial,
            'sales.invoices.due' => InvoiceStatus::Due,
            'sales.invoices.overdue' => InvoiceStatus::Overdue,
        ] as $route => $status) {
            if ($request->routeIs($route)) {
                return $status;
            }
        }

        if ($request->filled('status')) {
            return InvoiceStatus::tryFrom($request->string('status')->toString());
        }

        return null;
    }

    private function resolveTitle(Request $request): string
    {
        return match (true) {
            $request->routeIs('sales.invoices.paid') => 'Paid Invoices',
            $request->routeIs('sales.invoices.partial') => 'Partial Invoices',
            $request->routeIs('sales.invoices.due') => 'Due Invoices',
            $request->routeIs('sales.invoices.overdue') => 'Overdue Invoices',
            default => 'All Invoices',
        };
    }
}
