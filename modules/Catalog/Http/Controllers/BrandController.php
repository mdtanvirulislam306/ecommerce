<?php

namespace Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Http\Requests\StoreBrandRequest;
use Modules\Catalog\Http\Requests\UpdateBrandRequest;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Services\BrandService;

class BrandController extends Controller
{
    public function index(Request $request, BrandService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Catalog/Brands/Index', [
            'brands' => $service->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreBrandRequest $request, BrandService $service): RedirectResponse
    {
        $service->create($request->validated());

        return redirect()
            ->route('products.brands.index')
            ->with('success', 'Brand created successfully.');
    }

    public function quickStore(StoreBrandRequest $request, BrandService $service): JsonResponse
    {
        $brand = $service->create($request->validated());

        return response()->json([
            'item' => [
                'id' => $brand->id,
                'name' => $brand->name,
            ],
        ]);
    }

    public function update(UpdateBrandRequest $request, Brand $brand, BrandService $service): RedirectResponse
    {
        $service->update($brand, $request->validated());

        return redirect()
            ->route('products.brands.index')
            ->with('success', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand, BrandService $service): RedirectResponse
    {
        $service->delete($brand);

        return redirect()
            ->route('products.brands.index')
            ->with('success', 'Brand removed.');
    }
}
