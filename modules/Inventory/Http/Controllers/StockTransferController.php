<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Inventory\Http\Requests\StoreStockTransferRequest;
use Modules\Inventory\Services\StockService;
use Modules\Inventory\Services\StockTransferService;
use Modules\Inventory\Services\WarehouseService;

class StockTransferController extends Controller
{
    public function index(Request $request, StockTransferService $transfers): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Inventory/StockTransfers/Index', [
            'transfers' => $transfers->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function create(StockService $stock, WarehouseService $warehouses): Response
    {
        return Inertia::render('Inventory/StockTransfers/Create', [
            'warehouses' => $warehouses->options(),
            'productOptions' => $stock->searchProducts(),
        ]);
    }

    public function store(StoreStockTransferRequest $request, StockTransferService $transfers): RedirectResponse
    {
        $transfers->create($request->validated(), $request->user()->id);

        return redirect()
            ->route('inventory.stock-transfer.index')
            ->with('success', 'Stock transfer completed.');
    }
}
