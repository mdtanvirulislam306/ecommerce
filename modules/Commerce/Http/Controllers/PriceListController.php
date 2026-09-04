<?php

namespace Modules\Commerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Commerce\Http\Requests\StorePriceListItemRequest;
use Modules\Commerce\Http\Requests\StorePriceListRequest;
use Modules\Commerce\Http\Requests\UpdatePriceListItemRequest;
use Modules\Commerce\Http\Requests\UpdatePriceListRequest;
use Modules\Commerce\Models\PriceList;
use Modules\Commerce\Models\PriceListItem;
use Modules\Commerce\Services\PriceListService;

class PriceListController extends Controller
{
    public function index(Request $request, PriceListService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Commerce/PriceLists/Index', [
            'priceLists' => $service->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StorePriceListRequest $request, PriceListService $service): RedirectResponse
    {
        $priceList = $service->create($request->validated());

        return redirect()
            ->route('commerce.pricing.price-lists.show', $priceList)
            ->with('success', 'Price list created.');
    }

    public function show(PriceList $priceList, PriceListService $service): Response
    {
        return Inertia::render('Commerce/PriceLists/Show', [
            'priceList' => $service->formatForDetail($priceList),
            'productOptions' => $service->searchProducts(),
        ]);
    }

    public function update(UpdatePriceListRequest $request, PriceList $priceList, PriceListService $service): RedirectResponse
    {
        $service->update($priceList, $request->validated());

        return back()->with('success', 'Price list updated.');
    }

    public function destroy(PriceList $priceList, PriceListService $service): RedirectResponse
    {
        $service->delete($priceList);

        return redirect()
            ->route('commerce.pricing.price-lists.index')
            ->with('success', 'Price list removed.');
    }

    public function storeItem(StorePriceListItemRequest $request, PriceList $priceList, PriceListService $service): RedirectResponse
    {
        $service->addItem($priceList, $request->validated(), $request->user()->id);

        return back()->with('success', 'Price added.');
    }

    public function updateItem(
        UpdatePriceListItemRequest $request,
        PriceList $priceList,
        PriceListItem $item,
        PriceListService $service,
    ): RedirectResponse {
        abort_unless($item->price_list_id === $priceList->id, 404);
        $service->updateItem($item, $request->validated(), $request->user()->id);

        return back()->with('success', 'Price updated.');
    }

    public function destroyItem(Request $request, PriceList $priceList, PriceListItem $item, PriceListService $service): RedirectResponse
    {
        abort_unless($item->price_list_id === $priceList->id, 404);
        $service->deleteItem($item, $request->user()->id);

        return back()->with('success', 'Price removed.');
    }

    public function productSearch(Request $request, PriceListService $service): JsonResponse
    {
        return response()->json(
            $service->searchProducts($request->string('q')->trim()->toString() ?: null),
        );
    }

    public function productVariants(int $productId, PriceListService $service): JsonResponse
    {
        return response()->json($service->variantsForProduct($productId));
    }
}
