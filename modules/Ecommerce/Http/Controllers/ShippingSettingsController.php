<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Http\Requests\UpdateShippingSettingsRequest;
use Modules\Ecommerce\Services\DeliveryRateService;
use Modules\Ecommerce\Services\StoreSettingService;

class ShippingSettingsController extends Controller
{
    public function index(DeliveryRateService $delivery): Response
    {
        return Inertia::render('Ecommerce/Shipping/Index', [
            'settings' => [
                'shipping_enabled' => $delivery->enabled(),
                'zones' => collect($delivery->zones())->map(fn (array $zone) => [
                    'name' => $zone['name'],
                    'rate' => $zone['rate'],
                ])->all(),
                'free_shipping_threshold' => $delivery->freeThreshold(),
            ],
        ]);
    }

    public function update(UpdateShippingSettingsRequest $request, StoreSettingService $settings): RedirectResponse
    {
        $data = $request->validated();

        $settings->putMany([
            'shipping_enabled' => $data['shipping_enabled'] ? '1' : '0',
            'delivery_zones' => json_encode(collect($data['zones'] ?? [])->map(fn (array $zone) => [
                'name' => trim($zone['name']),
                'rate' => round((float) $zone['rate'], 2),
            ])->values()->all()),
            'free_shipping_threshold' => $data['free_shipping_threshold'] !== null
                ? (string) $data['free_shipping_threshold']
                : '',
        ]);

        return back()->with('success', 'Delivery settings saved.');
    }
}
