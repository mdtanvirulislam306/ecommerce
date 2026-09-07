<?php

namespace Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Http\Requests\ReceivePurchaseOrderRequest;
use Modules\Purchase\Http\Requests\StorePurchaseOrderRequest;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Services\PurchaseOrderService;
use Modules\Purchase\Services\SupplierService;

class PurchaseOrderController extends Controller
{
    public function index(Request $request, PurchaseOrderService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();
        $status = $this->resolveStatus($request);

        return Inertia::render('Purchase/Orders/Index', [
            'orders' => $service->listPaginated($search ?: null, $status, $perPage),
            'filters' => [
                'search' => $search,
                'status' => $status?->value,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'statusOptions' => collect(PurchaseOrderStatus::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function create(PurchaseOrderService $orders, SupplierService $suppliers): Response
    {
        return Inertia::render('Purchase/Orders/Create', [
            'suppliers' => $suppliers->options(),
            'warehouses' => $orders->warehouses(),
            'productOptions' => $orders->searchProducts(),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request, PurchaseOrderService $service): RedirectResponse
    {
        $order = $service->create($request->validated(), $request->user()->id);

        return redirect()
            ->route('purchase.orders.show', $order)
            ->with('success', 'Purchase order created.');
    }

    public function show(PurchaseOrder $order, PurchaseOrderService $service): Response
    {
        return Inertia::render('Purchase/Orders/Show', [
            'order' => $service->formatForDetail($order),
        ]);
    }

    public function approve(PurchaseOrder $order, PurchaseOrderService $service): RedirectResponse
    {
        $service->approve($order, request()->user()->id);

        return back()->with('success', 'Purchase order approved.');
    }

    public function receive(ReceivePurchaseOrderRequest $request, PurchaseOrder $order, PurchaseOrderService $service): RedirectResponse
    {
        $service->receive($order, $request->validated('items'), $request->user()->id);

        return back()->with('success', 'Goods received into inventory.');
    }

    public function cancel(PurchaseOrder $order, PurchaseOrderService $service): RedirectResponse
    {
        $service->cancel($order);

        return back()->with('success', 'Purchase order cancelled.');
    }

    public function productVariants(int $productId, PurchaseOrderService $service): JsonResponse
    {
        return response()->json($service->variantsForProduct($productId));
    }

    private function resolveStatus(Request $request): ?PurchaseOrderStatus
    {
        if (! $request->filled('status')) {
            return null;
        }

        return PurchaseOrderStatus::tryFrom($request->string('status')->toString());
    }
}
