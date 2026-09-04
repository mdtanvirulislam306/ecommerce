<?php

namespace Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Http\Requests\StoreUnitConversionRequest;
use Modules\Catalog\Http\Requests\StoreUnitRequest;
use Modules\Catalog\Http\Requests\UpdateUnitRequest;
use Modules\Catalog\Models\Unit;
use Modules\Catalog\Models\UnitConversion;
use Modules\Catalog\Services\UnitService;

class UnitController extends Controller
{
    public function index(Request $request, UnitService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Catalog/Units/Index', [
            'units' => $service->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreUnitRequest $request, UnitService $service): RedirectResponse
    {
        $service->create($request->validated());

        return redirect()
            ->route('products.units.index')
            ->with('success', 'Unit created successfully.');
    }

    public function update(UpdateUnitRequest $request, Unit $unit, UnitService $service): RedirectResponse
    {
        $service->update($unit, $request->validated());

        return redirect()
            ->route('products.units.index')
            ->with('success', 'Unit updated successfully.');
    }

    public function destroy(Unit $unit, UnitService $service): RedirectResponse
    {
        $service->delete($unit);

        return redirect()
            ->route('products.units.index')
            ->with('success', 'Unit removed.');
    }

    public function conversions(Request $request, UnitService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);

        return Inertia::render('Catalog/Units/Conversions', [
            'conversions' => $service->listConversionsPaginated($perPage),
            'units' => Unit::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'filters' => [
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function storeConversion(StoreUnitConversionRequest $request, UnitService $service): RedirectResponse
    {
        $service->upsertConversion($request->validated());

        return redirect()
            ->route('products.units.conversions.index')
            ->with('success', 'Conversion saved successfully.');
    }

    public function destroyConversion(UnitConversion $conversion, UnitService $service): RedirectResponse
    {
        $service->deleteConversion($conversion);

        return redirect()
            ->route('products.units.conversions.index')
            ->with('success', 'Conversion removed.');
    }
}
