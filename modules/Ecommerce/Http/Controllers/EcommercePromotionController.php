<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Enums\DiscountType;
use Modules\Ecommerce\Http\Requests\StoreEcommercePromotionRequest;
use Modules\Ecommerce\Http\Requests\UpdateEcommercePromotionRequest;
use Modules\Ecommerce\Models\EcommercePromotion;
use Modules\Ecommerce\Services\EcommercePromotionService;

class EcommercePromotionController extends Controller
{
    public function index(Request $request, EcommercePromotionService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Ecommerce/Promotions/Index', [
            'promotions' => $service->listPaginated($search ?: null, $perPage),
            'typeOptions' => collect(DiscountType::cases())->map(fn (DiscountType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->values(),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreEcommercePromotionRequest $request, EcommercePromotionService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Promotion created.');
    }

    public function update(UpdateEcommercePromotionRequest $request, EcommercePromotion $ecommercePromotion, EcommercePromotionService $service): RedirectResponse
    {
        $service->update($ecommercePromotion, $request->validated());

        return back()->with('success', 'Promotion updated.');
    }

    public function destroy(EcommercePromotion $ecommercePromotion, EcommercePromotionService $service): RedirectResponse
    {
        $service->delete($ecommercePromotion);

        return back()->with('success', 'Promotion removed.');
    }
}
