<?php

namespace Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Http\Requests\StoreCollectionRequest;
use Modules\Catalog\Http\Requests\UpdateCollectionRequest;
use Modules\Catalog\Models\Collection;
use Modules\Catalog\Services\CollectionService;

class CollectionController extends Controller
{
    public function index(Request $request, CollectionService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Catalog/Collections/Index', [
            'collections' => $service->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreCollectionRequest $request, CollectionService $service): RedirectResponse
    {
        $service->create($request->validated());

        return redirect()
            ->route('products.collections.index')
            ->with('success', 'Collection created successfully.');
    }

    public function quickStore(StoreCollectionRequest $request, CollectionService $service): JsonResponse
    {
        $collection = $service->create($request->validated());

        return response()->json([
            'item' => [
                'id' => $collection->id,
                'name' => $collection->name,
            ],
        ]);
    }

    public function update(UpdateCollectionRequest $request, Collection $collection, CollectionService $service): RedirectResponse
    {
        $service->update($collection, $request->validated());

        return redirect()
            ->route('products.collections.index')
            ->with('success', 'Collection updated successfully.');
    }

    public function destroy(Collection $collection, CollectionService $service): RedirectResponse
    {
        $service->delete($collection);

        return redirect()
            ->route('products.collections.index')
            ->with('success', 'Collection removed.');
    }
}
