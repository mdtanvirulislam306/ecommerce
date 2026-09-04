<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Inventory\Enums\StockMovementType;
use Modules\Inventory\Http\Requests\StoreStockAdjustmentRequest;
use Modules\Inventory\Services\StockService;
use Modules\Inventory\Services\WarehouseService;

class StockController extends Controller
{
    public function overview(Request $request, StockService $stock, WarehouseService $warehouses): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();
        $warehouseId = $request->filled('warehouse_id') ? $request->integer('warehouse_id') : null;

        $stockFilter = $request->string('stock')->toString() ?: null;

        if ($request->routeIs('inventory.low-stock.index')) {
            $stockFilter = 'low';
        } elseif ($request->routeIs('inventory.out-of-stock.index')) {
            $stockFilter = 'out';
        }

        if (! in_array($stockFilter, ['low', 'out'], true)) {
            $stockFilter = null;
        }

        return Inertia::render('Inventory/Stock/Overview', [
            'levels' => $stock->listLevels($search ?: null, $warehouseId, $stockFilter, $perPage),
            'warehouses' => $warehouses->options(),
            'stats' => $stock->overviewStats(),
            'filters' => [
                'search' => $search,
                'warehouse_id' => $warehouseId,
                'stock' => $stockFilter,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function movements(Request $request, StockService $stock, WarehouseService $warehouses): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();
        $warehouseId = $request->filled('warehouse_id') ? $request->integer('warehouse_id') : null;
        $type = $request->filled('type')
            ? StockMovementType::tryFrom($request->string('type')->toString())
            : null;

        return Inertia::render('Inventory/Stock/Movements', [
            'movements' => $stock->listMovements($search ?: null, $warehouseId, $type, $perPage),
            'warehouses' => $warehouses->options(),
            'types' => collect(StockMovementType::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
            'filters' => [
                'search' => $search,
                'warehouse_id' => $warehouseId,
                'type' => $type?->value,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function adjustmentForm(StockService $stock, WarehouseService $warehouses): Response
    {
        return Inertia::render('Inventory/Stock/Adjustment', [
            'warehouses' => $warehouses->options(),
            'productOptions' => $stock->searchProducts(),
        ]);
    }

    public function adjust(StoreStockAdjustmentRequest $request, StockService $stock): RedirectResponse
    {
        $stock->adjust($request->validated(), $request->user()->id);

        return redirect()
            ->route('inventory.stock-movement.index')
            ->with('success', 'Stock adjustment recorded.');
    }

    public function productVariants(int $productId, StockService $stock): JsonResponse
    {
        return response()->json($stock->variantsForProduct($productId));
    }
}
