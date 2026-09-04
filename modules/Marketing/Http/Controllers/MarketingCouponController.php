<?php

namespace Modules\Marketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Marketing\Http\Requests\StoreMarketingCouponRequest;
use Modules\Marketing\Http\Requests\UpdateMarketingCouponRequest;
use Modules\Marketing\Models\MarketingCoupon;
use Modules\Marketing\Services\MarketingCouponService;

class MarketingCouponController extends Controller
{
    public function index(Request $request, MarketingCouponService $coupons): Response
    {
        return Inertia::render('Marketing/Coupons/Index', [
            'coupons' => $coupons->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'per_page' => $request->integer('per_page', 25),
            ],
            'types' => $coupons->typeOptions(),
        ]);
    }

    public function store(StoreMarketingCouponRequest $request, MarketingCouponService $coupons): RedirectResponse
    {
        $coupons->create($request->validated());

        return back()->with('success', 'Coupon created.');
    }

    public function update(UpdateMarketingCouponRequest $request, MarketingCoupon $coupon, MarketingCouponService $coupons): RedirectResponse
    {
        $coupons->update($coupon, $request->validated());

        return back()->with('success', 'Coupon updated.');
    }

    public function destroy(MarketingCoupon $coupon, MarketingCouponService $coupons): RedirectResponse
    {
        $coupons->delete($coupon);

        return back()->with('success', 'Coupon deleted.');
    }
}
