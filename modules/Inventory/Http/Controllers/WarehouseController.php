<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Inventory\Http\Requests\StoreWarehouseRequest;
use Modules\Inventory\Http\Requests\UpdateWarehouseRequest;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\WarehouseService;

class WarehouseController extends Controller
{
    public function index(Request $request, WarehouseService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Inventory/Warehouses/Index', [
            'warehouses' => $service->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreWarehouseRequest $request, WarehouseService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Warehouse created.');
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse, WarehouseService $service): RedirectResponse
    {
        $service->update($warehouse, $request->validated());

        return back()->with('success', 'Warehouse updated.');
    }

    public function destroy(Warehouse $warehouse, WarehouseService $service): RedirectResponse
    {
        $service->delete($warehouse);

        return back()->with('success', 'Warehouse removed.');
    }
}
