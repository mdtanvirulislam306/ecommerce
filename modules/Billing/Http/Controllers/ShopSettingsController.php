<?php

namespace Modules\Billing\Http\Controllers;

use App\Core\Support\ShopComplexity;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Http\Requests\UpdateShopSettingsRequest;
use Modules\Billing\Models\ShopSetting;

class ShopSettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Billing/Settings/Index', [
            'flags' => ShopComplexity::flags(),
        ]);
    }

    public function update(UpdateShopSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach (['multi_price', 'multi_warehouse', 'multi_branch'] as $key) {
            ShopSetting::setValue($key, (bool) ($data[$key] ?? false));
        }

        return back()->with('success', 'Shop complexity settings saved.');
    }
}
