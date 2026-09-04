<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Sales\Enums\QuotationStatus;
use Modules\Sales\Http\Requests\StoreQuotationRequest;
use Modules\Sales\Http\Requests\UpdateQuotationRequest;
use Modules\Sales\Models\SalesQuotation;
use Modules\Sales\Services\QuotationService;
use Modules\Sales\Services\SalesOrderService;

class QuotationController extends Controller
{
    public function index(Request $request, QuotationService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();
        $status = $this->resolveStatus($request);

        return Inertia::render('Sales/Quotations/Index', [
            'quotations' => $service->listPaginated($search ?: null, $status, $perPage),
            'filters' => [
                'search' => $search,
                'status' => $status?->value,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'listTitle' => $this->resolveTitle($request),
            'statusOptions' => collect(QuotationStatus::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function create(SalesOrderService $orders): Response
    {
        return Inertia::render('Sales/Quotations/Create', [
            'productOptions' => $orders->searchProducts(),
            'customerGroups' => $orders->customerGroups(),
            'warehouses' => $orders->warehouses(),
        ]);
    }

    public function store(StoreQuotationRequest $request, QuotationService $service): RedirectResponse
    {
        $quotation = $service->create($request->validated(), $request->user()->id);

        return redirect()
            ->route('sales.quotations.show', $quotation)
            ->with('success', 'Quotation created.');
    }

    public function show(SalesQuotation $quotation, QuotationService $service): Response
    {
        return Inertia::render('Sales/Quotations/Show', [
            'quotation' => $service->formatForDetail($quotation),
        ]);
    }

    public function edit(SalesQuotation $quotation, QuotationService $service, SalesOrderService $orders): Response
    {
        return Inertia::render('Sales/Quotations/Edit', [
            'quotation' => $service->formatForDetail($quotation),
            'productOptions' => $orders->searchProducts(),
            'customerGroups' => $orders->customerGroups(),
            'warehouses' => $orders->warehouses(),
        ]);
    }

    public function update(
        UpdateQuotationRequest $request,
        SalesQuotation $quotation,
        QuotationService $service,
    ): RedirectResponse {
        $service->update($quotation, $request->validated());

        return redirect()
            ->route('sales.quotations.show', $quotation)
            ->with('success', 'Quotation updated.');
    }

    public function markSent(SalesQuotation $quotation, QuotationService $service): RedirectResponse
    {
        $service->markStatus($quotation, QuotationStatus::Sent);

        return back()->with('success', 'Quotation marked as sent.');
    }

    public function markAccepted(SalesQuotation $quotation, QuotationService $service): RedirectResponse
    {
        $service->markStatus($quotation, QuotationStatus::Accepted);

        return back()->with('success', 'Quotation accepted.');
    }

    public function markExpired(SalesQuotation $quotation, QuotationService $service): RedirectResponse
    {
        $service->markStatus($quotation, QuotationStatus::Expired);

        return back()->with('success', 'Quotation expired.');
    }

    public function convert(SalesQuotation $quotation, QuotationService $service): RedirectResponse
    {
        $order = $service->convertToOrder($quotation, request()->user()->id);

        return redirect()
            ->route('sales.orders.show', $order)
            ->with('success', 'Quotation converted to sales order.');
    }

    private function resolveStatus(Request $request): ?QuotationStatus
    {
        foreach ([
            'sales.quotations.draft' => QuotationStatus::Draft,
            'sales.quotations.sent' => QuotationStatus::Sent,
            'sales.quotations.accepted' => QuotationStatus::Accepted,
            'sales.quotations.expired' => QuotationStatus::Expired,
        ] as $route => $status) {
            if ($request->routeIs($route)) {
                return $status;
            }
        }

        if ($request->filled('status')) {
            return QuotationStatus::tryFrom($request->string('status')->toString());
        }

        return null;
    }

    private function resolveTitle(Request $request): string
    {
        return match (true) {
            $request->routeIs('sales.quotations.draft') => 'Draft Quotations',
            $request->routeIs('sales.quotations.sent') => 'Sent Quotations',
            $request->routeIs('sales.quotations.accepted') => 'Accepted Quotations',
            $request->routeIs('sales.quotations.expired') => 'Expired Quotations',
            default => 'All Quotations',
        };
    }
}
