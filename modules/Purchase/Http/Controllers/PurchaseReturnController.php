<?php

namespace Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Purchase\Http\Requests\StorePurchaseReturnRequest;
use Modules\Purchase\Services\PurchaseReturnService;

class PurchaseReturnController extends Controller
{
    public function index(Request $request, PurchaseReturnService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Purchase/Returns/Index', [
            'returns' => $service->listPaginated($search ?: null, $perPage),
            'orders' => $service->receivableOrders(),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StorePurchaseReturnRequest $request, PurchaseReturnService $service): RedirectResponse
    {
        $service->create($request->validated(), $request->user()->id);

        return back()->with('success', 'Purchase return recorded and stock reversed.');
    }
}
