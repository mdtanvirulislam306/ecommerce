<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Http\Requests\UpdateShippingSettingsRequest;
use Modules\Ecommerce\Services\StoreSettingService;

class ShippingSettingsController extends Controller
{
    public function index(StoreSettingService $settings): Response
    {
        return Inertia::render('Ecommerce/Shipping/Index', [
            'settings' => $settings->getMany(
                ['shipping_enabled', 'default_rate', 'free_shipping_threshold'],
                ['shipping_enabled' => '1', 'default_rate' => '0', 'free_shipping_threshold' => ''],
            ),
        ]);
    }

    public function update(UpdateShippingSettingsRequest $request, StoreSettingService $settings): RedirectResponse
    {
        $data = $request->validated();
        $settings->putMany([
            'shipping_enabled' => ($data['shipping_enabled'] ?? false) ? '1' : '0',
            'default_rate' => (string) ($data['default_rate'] ?? '0'),
            'free_shipping_threshold' => $data['free_shipping_threshold'] !== null
                ? (string) $data['free_shipping_threshold']
                : '',
        ]);

        return back()->with('success', 'Shipping settings saved.');
    }
}
