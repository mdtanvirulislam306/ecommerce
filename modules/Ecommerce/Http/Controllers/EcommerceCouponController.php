<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Enums\DiscountType;
use Modules\Ecommerce\Http\Requests\StoreEcommerceCouponRequest;
use Modules\Ecommerce\Http\Requests\UpdateEcommerceCouponRequest;
use Modules\Ecommerce\Models\EcommerceCoupon;
use Modules\Ecommerce\Services\EcommerceCouponService;

class EcommerceCouponController extends Controller
{
    public function index(Request $request, EcommerceCouponService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Ecommerce/Coupons/Index', [
            'coupons' => $service->listPaginated($search ?: null, $perPage),
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

    public function store(StoreEcommerceCouponRequest $request, EcommerceCouponService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Coupon created.');
    }

    public function update(UpdateEcommerceCouponRequest $request, EcommerceCoupon $ecommerceCoupon, EcommerceCouponService $service): RedirectResponse
    {
        $service->update($ecommerceCoupon, $request->validated());

        return back()->with('success', 'Coupon updated.');
    }

    public function destroy(EcommerceCoupon $ecommerceCoupon, EcommerceCouponService $service): RedirectResponse
    {
        $service->delete($ecommerceCoupon);

        return back()->with('success', 'Coupon removed.');
    }
}
