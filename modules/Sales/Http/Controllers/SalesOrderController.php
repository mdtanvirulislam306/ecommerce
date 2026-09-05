<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Http\Requests\StoreSalesOrderRequest;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\SalesOrderService;

class SalesOrderController extends Controller
{
    public function index(Request $request, SalesOrderService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        $status = $this->resolveStatus($request);

        return Inertia::render('Sales/Orders/Index', [
            'orders' => $service->listPaginated($search ?: null, $status, $perPage),
            'filters' => [
                'search' => $search,
                'status' => $status?->value,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'listTitle' => $this->resolveTitle($request),
            'statusOptions' => collect(SalesOrderStatus::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function create(Request $request, SalesOrderService $service): Response
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

    public function show(SalesOrder $order, SalesOrderService $service): Response
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
        if ($request->routeIs('sales.orders.pending')) {
            return SalesOrderStatus::Pending;
        }

        if ($request->routeIs('sales.orders.confirmed')) {
            return SalesOrderStatus::Confirmed;
        }

        if ($request->routeIs('sales.orders.cancelled')) {
            return SalesOrderStatus::Cancelled;
        }

        if ($request->filled('status')) {
            return SalesOrderStatus::tryFrom($request->string('status')->toString());
        }

        return null;
    }

    private function resolveTitle(Request $request): string
    {
        return match (true) {
            $request->routeIs('sales.orders.pending') => 'Pending Orders',
            $request->routeIs('sales.orders.confirmed') => 'Confirmed Orders',
            $request->routeIs('sales.orders.cancelled') => 'Cancelled Orders',
            default => 'All Orders',
        };
    }
}
