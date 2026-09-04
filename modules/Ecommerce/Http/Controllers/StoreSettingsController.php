<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Http\Requests\UpdateStoreSettingsRequest;
use Modules\Ecommerce\Services\StoreSettingService;

class StoreSettingsController extends Controller
{
    public function index(StoreSettingService $settings): Response
    {
        return Inertia::render('Ecommerce/Store/Settings', [
            'settings' => $settings->getMany(
                ['store_name', 'support_email', 'currency', 'timezone'],
                [
                    'store_name' => config('app.name'),
                    'support_email' => '',
                    'currency' => 'BDT',
                    'timezone' => config('app.timezone'),
                ],
            ),
        ]);
    }

    public function update(UpdateStoreSettingsRequest $request, StoreSettingService $settings): RedirectResponse
    {
        $settings->putMany($request->validated());

        return back()->with('success', 'Store settings saved.');
    }
}
