<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Http\Requests\UpdateCheckoutSettingsRequest;
use Modules\Ecommerce\Services\StoreSettingService;

class CheckoutSettingsController extends Controller
{
    public function index(StoreSettingService $settings): Response
    {
        return Inertia::render('Ecommerce/Checkout/Index', [
            'settings' => $settings->getMany(
                ['guest_checkout', 'require_phone', 'payment_cod_enabled'],
                ['guest_checkout' => '1', 'require_phone' => '0', 'payment_cod_enabled' => '1'],
            ),
        ]);
    }

    public function update(UpdateCheckoutSettingsRequest $request, StoreSettingService $settings): RedirectResponse
    {
        $data = $request->validated();
        $settings->putMany([
            'guest_checkout' => ($data['guest_checkout'] ?? false) ? '1' : '0',
            'require_phone' => ($data['require_phone'] ?? false) ? '1' : '0',
            'payment_cod_enabled' => ($data['payment_cod_enabled'] ?? false) ? '1' : '0',
        ]);

        return back()->with('success', 'Checkout settings saved.');
    }
}
