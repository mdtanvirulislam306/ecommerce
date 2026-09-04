<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Services\SettingService;

class GeneralSettingsController extends Controller
{
    public function edit(SettingService $settings): Response
    {
        return Inertia::render('Settings/General/Index', [
            'settings' => array_merge([
                'shop_name' => '',
                'timezone' => 'Asia/Dhaka',
                'locale' => 'en',
                'default_currency' => 'BDT',
            ], $settings->getGroup('general')),
        ]);
    }

    public function update(Request $request, SettingService $settings): RedirectResponse
    {
        $data = $request->validate([
            'shop_name' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:80'],
            'locale' => ['nullable', 'string', 'max:20'],
            'default_currency' => ['nullable', 'string', 'max:10'],
        ]);
        $settings->saveGroup('general', $data);

        return back()->with('success', 'General settings saved.');
    }
}
