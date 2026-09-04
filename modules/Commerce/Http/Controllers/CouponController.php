<?php

namespace Modules\Commerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Commerce\Http\Requests\StoreCouponRequest;
use Modules\Commerce\Http\Requests\UpdateCouponRequest;
use Modules\Commerce\Models\Coupon;
use Modules\Commerce\Services\CouponService;

class CouponController extends Controller
{
    public function index(Request $request, CouponService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Commerce/Coupons/Index', [
            'coupons' => $service->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreCouponRequest $request, CouponService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Coupon created.');
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon, CouponService $service): RedirectResponse
    {
        $service->update($coupon, $request->validated());

        return back()->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon, CouponService $service): RedirectResponse
    {
        $service->delete($coupon);

        return back()->with('success', 'Coupon removed.');
    }
}
