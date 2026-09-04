<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Inventory\Http\Requests\StoreInventoryBatchRequest;
use Modules\Inventory\Http\Requests\StoreInventorySerialRequest;
use Modules\Inventory\Http\Requests\UpdateInventoryBatchRequest;
use Modules\Inventory\Http\Requests\UpdateInventorySerialRequest;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Models\InventorySerialNumber;
use Modules\Inventory\Services\InventoryTrackingService;
use Modules\Inventory\Services\WarehouseService;

class InventoryTrackingController extends Controller
{
    public function batches(Request $request, InventoryTrackingService $tracking, WarehouseService $warehouses): Response
    {
        return Inertia::render('Inventory/Batches/Index', [
            'batches' => $tracking->listBatches(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
            ),
            'warehouses' => $warehouses->options(),
            'products' => $this->productOptions(),
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function storeBatch(StoreInventoryBatchRequest $request, InventoryTrackingService $tracking): RedirectResponse
    {
        $tracking->createBatch($request->validated());

        return back()->with('success', 'Batch created.');
    }

    public function updateBatch(UpdateInventoryBatchRequest $request, InventoryBatch $inventoryBatch, InventoryTrackingService $tracking): RedirectResponse
    {
        $tracking->updateBatch($inventoryBatch, $request->validated());

        return back()->with('success', 'Batch updated.');
    }

    public function destroyBatch(InventoryBatch $inventoryBatch, InventoryTrackingService $tracking): RedirectResponse
    {
        $tracking->deleteBatch($inventoryBatch);

        return back()->with('success', 'Batch deleted.');
    }

    public function serials(Request $request, InventoryTrackingService $tracking, WarehouseService $warehouses): Response
    {
        return Inertia::render('Inventory/Serials/Index', [
            'serials' => $tracking->listSerials(
                $request->string('search')->trim()->toString() ?: null,
                $request->string('status')->toString() ?: null,
                $request->integer('per_page', 25),
            ),
            'warehouses' => $warehouses->options(),
            'products' => $this->productOptions(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function storeSerial(StoreInventorySerialRequest $request, InventoryTrackingService $tracking): RedirectResponse
    {
        $tracking->createSerial($request->validated());

        return back()->with('success', 'Serial number created.');
    }

    public function updateSerial(UpdateInventorySerialRequest $request, InventorySerialNumber $inventorySerialNumber, InventoryTrackingService $tracking): RedirectResponse
    {
        $tracking->updateSerial($inventorySerialNumber, $request->validated());

        return back()->with('success', 'Serial number updated.');
    }

    public function destroySerial(InventorySerialNumber $inventorySerialNumber, InventoryTrackingService $tracking): RedirectResponse
    {
        $tracking->deleteSerial($inventorySerialNumber);

        return back()->with('success', 'Serial number deleted.');
    }

    public function valuation(Request $request, InventoryTrackingService $tracking, WarehouseService $warehouses): Response
    {
        $warehouseId = $request->filled('warehouse_id') ? $request->integer('warehouse_id') : null;

        return Inertia::render('Inventory/Valuation/Index', [
            'valuation' => $tracking->valuation($warehouseId),
            'warehouses' => $warehouses->options(),
            'filters' => ['warehouse_id' => $warehouseId],
        ]);
    }

    public function reports(InventoryTrackingService $tracking): Response
    {
        return Inertia::render('Inventory/Reports/Index', [
            'stats' => $tracking->reportStats(),
        ]);
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function productOptions(): array
    {
        return \Illuminate\Support\Facades\DB::table('products')
            ->where('status', '!=', 'archived')
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->map(fn ($row) => ['id' => $row->id, 'name' => $row->name])
            ->all();
    }
}
