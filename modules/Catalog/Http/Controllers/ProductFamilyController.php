<?php

namespace Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Http\Requests\StoreProductFamilyRequest;
use Modules\Catalog\Http\Requests\UpdateProductFamilyRequest;
use Modules\Catalog\Models\ProductFamily;
use Modules\Catalog\Services\ProductFamilyService;

class ProductFamilyController extends Controller
{
    public function index(Request $request, ProductFamilyService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Catalog/Families/Index', [
            'families' => $service->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreProductFamilyRequest $request, ProductFamilyService $service): RedirectResponse
    {
        $service->create($request->validated());

        return redirect()
            ->route('products.families.index')
            ->with('success', 'Product family created successfully.');
    }

    public function update(UpdateProductFamilyRequest $request, ProductFamily $family, ProductFamilyService $service): RedirectResponse
    {
        $service->update($family, $request->validated());

        return redirect()
            ->route('products.families.index')
            ->with('success', 'Product family updated successfully.');
    }

    public function destroy(ProductFamily $family, ProductFamilyService $service): RedirectResponse
    {
        $service->delete($family);

        return redirect()
            ->route('products.families.index')
            ->with('success', 'Product family removed.');
    }
}
