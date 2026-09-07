<?php

namespace Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Http\Requests\StoreCategoryRequest;
use Modules\Catalog\Http\Requests\UpdateCategoryRequest;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Services\CategoryService;

class CategoryController extends Controller
{
    public function index(Request $request, CategoryService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Catalog/Categories/Index', [
            'categories' => $service->listPaginated($search ?: null, null, $perPage),
            'parentOptions' => Category::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'parent_id']),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreCategoryRequest $request, CategoryService $service): RedirectResponse
    {
        $service->create($request->validated());

        return redirect()
            ->route('products.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function quickStore(StoreCategoryRequest $request, CategoryService $service): JsonResponse
    {
        $category = $service->create($request->validated());

        return response()->json([
            'item' => [
                'id' => $category->id,
                'name' => $category->name,
                'parent_id' => $category->parent_id,
            ],
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category, CategoryService $service): RedirectResponse
    {
        $service->update($category, $request->validated());

        return redirect()
            ->route('products.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category, CategoryService $service): RedirectResponse
    {
        $service->delete($category);

        return redirect()
            ->route('products.categories.index')
            ->with('success', 'Category removed.');
    }
}
