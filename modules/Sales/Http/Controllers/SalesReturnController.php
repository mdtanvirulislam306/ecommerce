<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Sales\Http\Requests\StoreSalesReturnRequest;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesReturn;
use Modules\Sales\Services\SalesOrderService;
use Modules\Sales\Services\SalesReturnService;

class SalesReturnController extends Controller
{
    public function index(Request $request, SalesReturnService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Sales/Returns/Index', [
            'returns' => $service->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function create(SalesOrderService $orders): Response
    {
        $invoices = SalesInvoice::query()
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['id', 'number', 'customer_name', 'sales_order_id', 'currency'])
            ->map(fn (SalesInvoice $invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'customer_name' => $invoice->customer_name,
                'sales_order_id' => $invoice->sales_order_id,
                'currency' => $invoice->currency,
            ])
            ->all();

        $orderOptions = SalesOrder::query()
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['id', 'number', 'customer_name', 'warehouse_id', 'currency'])
            ->map(fn (SalesOrder $order) => [
                'id' => $order->id,
                'number' => $order->number,
                'customer_name' => $order->customer_name,
                'warehouse_id' => $order->warehouse_id,
                'currency' => $order->currency,
            ])
            ->all();

        return Inertia::render('Sales/Returns/Create', [
            'invoices' => $invoices,
            'orders' => $orderOptions,
            'warehouses' => $orders->warehouses(),
            'productOptions' => $orders->searchProducts(),
        ]);
    }

    public function store(StoreSalesReturnRequest $request, SalesReturnService $service): RedirectResponse
    {
        $return = $service->create($request->validated(), $request->user()->id);

        return redirect()
            ->route('sales.returns.show', $return)
            ->with('success', 'Sales return created.');
    }

    public function show(SalesReturn $salesReturn, SalesReturnService $service): Response
    {
        return Inertia::render('Sales/Returns/Show', [
            'returnRecord' => $service->formatForDetail($salesReturn),
        ]);
    }

    public function confirm(SalesReturn $salesReturn, SalesReturnService $service): RedirectResponse
    {
        $service->confirm($salesReturn, request()->user()->id);

        return back()->with('success', 'Return confirmed. Stock restocked.');
    }
}
