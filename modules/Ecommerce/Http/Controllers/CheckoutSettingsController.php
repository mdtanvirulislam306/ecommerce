<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Http\Requests\UpdateCheckoutSettingsRequest;
use Modules\Ecommerce\Services\PaymentSettingService;

class CheckoutSettingsController extends Controller
{
    public function index(PaymentSettingService $payments): Response
    {
        return Inertia::render('Ecommerce/Checkout/Index', [
            'settings' => $payments->adminSettings(),
            'callbackUrls' => [
                'success' => route('shop.payments.sslcommerz.success'),
                'ipn' => route('shop.payments.sslcommerz.ipn'),
            ],
        ]);
    }

    public function update(UpdateCheckoutSettingsRequest $request, PaymentSettingService $payments): RedirectResponse
    {
        $payments->save($request->validated());

        return back()->with('success', 'Payment & checkout settings saved.');
    }
}
