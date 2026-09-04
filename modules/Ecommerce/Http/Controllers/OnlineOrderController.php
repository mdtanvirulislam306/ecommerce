<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Enums\OnlineOrderStatus;
use Modules\Ecommerce\Models\OnlineOrder;
use Modules\Ecommerce\Services\OnlineOrderService;

class OnlineOrderController extends Controller
{
    public function index(Request $request, OnlineOrderService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();
        $status = $request->filled('status')
            ? OnlineOrderStatus::tryFrom($request->string('status')->toString())
            : null;

        return Inertia::render('Ecommerce/OnlineOrders/Index', [
            'orders' => $service->listPaginated($search ?: null, $status, $perPage),
            'filters' => [
                'search' => $search,
                'status' => $status?->value,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'statusOptions' => collect(OnlineOrderStatus::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function show(OnlineOrder $onlineOrder, OnlineOrderService $service): Response
    {
        return Inertia::render('Ecommerce/OnlineOrders/Show', [
            'order' => $service->formatDetail($onlineOrder),
        ]);
    }

    public function confirm(OnlineOrder $onlineOrder, OnlineOrderService $service): RedirectResponse
    {
        $service->confirm($onlineOrder);

        return back()->with('success', 'Order confirmed and stock fulfilled.');
    }

    public function cancel(OnlineOrder $onlineOrder, OnlineOrderService $service): RedirectResponse
    {
        $service->cancel($onlineOrder);

        return back()->with('success', 'Order cancelled.');
    }
}
