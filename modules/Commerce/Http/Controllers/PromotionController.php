<?php

namespace Modules\Commerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Commerce\Http\Requests\StorePromotionRequest;
use Modules\Commerce\Http\Requests\UpdatePromotionRequest;
use Modules\Commerce\Models\Promotion;
use Modules\Commerce\Services\PromotionService;

class PromotionController extends Controller
{
    public function index(Request $request, PromotionService $service): Response
    {
        return $this->renderIndex($request, $service);
    }

    public function discountRules(Request $request, PromotionService $service): Response
    {
        return $this->renderIndex($request, $service, 'discount_rule', 'Discount rules');
    }

    public function buyXGetY(Request $request, PromotionService $service): Response
    {
        return $this->renderIndex($request, $service, 'buy_x_get_y', 'Buy X get Y');
    }

    public function freeShipping(Request $request, PromotionService $service): Response
    {
        return $this->renderIndex($request, $service, 'free_shipping', 'Free shipping');
    }

    public function store(StorePromotionRequest $request, PromotionService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Promotion created.');
    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion, PromotionService $service): RedirectResponse
    {
        $service->update($promotion, $request->validated());

        return back()->with('success', 'Promotion updated.');
    }

    public function destroy(Promotion $promotion, PromotionService $service): RedirectResponse
    {
        $service->delete($promotion);

        return back()->with('success', 'Promotion removed.');
    }

    private function renderIndex(Request $request, PromotionService $service, ?string $type = null, ?string $pageTitle = null): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Commerce/Promotions/Index', [
            'promotions' => $service->listPaginated($search ?: null, $perPage, $type),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
                'type' => $type,
            ],
            'perPageOptions' => [10, 25, 50, 100],
            'pageTitle' => $pageTitle ?? 'All promotions',
            'fixedType' => $type,
        ]);
    }
}
