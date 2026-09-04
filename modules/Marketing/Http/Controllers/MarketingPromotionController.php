<?php

namespace Modules\Marketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Marketing\Http\Requests\StoreMarketingPromotionRequest;
use Modules\Marketing\Http\Requests\UpdateMarketingPromotionRequest;
use Modules\Marketing\Models\MarketingPromotion;
use Modules\Marketing\Services\MarketingPromotionService;

class MarketingPromotionController extends Controller
{
    public function index(Request $request, MarketingPromotionService $promotions): Response
    {
        return Inertia::render('Marketing/Promotions/Index', [
            'promotions' => $promotions->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'per_page' => $request->integer('per_page', 25),
            ],
            'types' => $promotions->typeOptions(),
        ]);
    }

    public function store(StoreMarketingPromotionRequest $request, MarketingPromotionService $promotions): RedirectResponse
    {
        $promotions->create($request->validated());

        return back()->with('success', 'Promotion created.');
    }

    public function update(UpdateMarketingPromotionRequest $request, MarketingPromotion $promotion, MarketingPromotionService $promotions): RedirectResponse
    {
        $promotions->update($promotion, $request->validated());

        return back()->with('success', 'Promotion updated.');
    }

    public function destroy(MarketingPromotion $promotion, MarketingPromotionService $promotions): RedirectResponse
    {
        $promotions->delete($promotion);

        return back()->with('success', 'Promotion deleted.');
    }
}
