<?php

namespace Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Enums\ProductType;
use Modules\Catalog\Http\Requests\StoreProductRequest;
use Modules\Catalog\Http\Requests\UpdateProductRequest;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Services\ProductService;

class ProductController extends Controller
{
    public function index(Request $request, ProductService $service): Response
    {
        $status = $request->filled('status')
            ? ProductStatus::tryFrom($request->string('status')->toString())
            : null;
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        $type = $request->filled('type')
            ? ProductType::tryFrom($request->string('type')->toString())
            : null;

        return Inertia::render('Catalog/Products/Index', [
            'products' => $service->listPaginated(
                search: $search ?: null,
                status: $status,
                type: $type,
                perPage: $perPage,
            ),
            'filters' => [
                'search' => $search,
                'status' => $status?->value,
                'type' => $type?->value,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'statusOptions' => collect(ProductStatus::cases())->map(fn (ProductStatus $case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->values()->all(),
            'typeOptions' => [
                ['value' => 'simple', 'label' => 'Simple'],
                ['value' => 'variant', 'label' => 'Variant'],
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function create(ProductService $service): Response
    {
        return Inertia::render('Catalog/Products/Create', [
            'options' => $service->formOptions(),
        ]);
    }

    public function store(StoreProductRequest $request, ProductService $service): RedirectResponse
    {
        $product = $service->create(
            $request->validated(),
            $request->file('media', []),
        );

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product, ProductService $service): Response
    {
        return Inertia::render('Catalog/Products/Show', [
            'product' => $service->findForDetail($product),
        ]);
    }

    public function edit(Product $product, ProductService $service): Response
    {
        return Inertia::render('Catalog/Products/Edit', [
            'product' => $service->findForDetail($product),
            'options' => $service->formOptions(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, ProductService $service): RedirectResponse
    {
        $service->update(
            $product,
            $request->validated(),
            $request->file('media', []),
        );

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product, ProductService $service): RedirectResponse
    {
        $service->delete($product);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product removed.');
    }
}
