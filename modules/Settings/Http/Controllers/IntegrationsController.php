<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Services\SettingService;

class IntegrationsController extends Controller
{
    public function edit(SettingService $settings): Response
    {
        return Inertia::render('Settings/Integrations/Index', [
            'settings' => array_merge([
                'stripe_key' => '',
                'sms_api_key' => '',
                'google_maps_key' => '',
            ], $settings->getGroup('integrations')),
        ]);
    }

    public function update(Request $request, SettingService $settings): RedirectResponse
    {
        $data = $request->validate([
            'stripe_key' => ['nullable', 'string', 'max:255'],
            'sms_api_key' => ['nullable', 'string', 'max:255'],
            'google_maps_key' => ['nullable', 'string', 'max:255'],
        ]);
        $settings->saveGroup('integrations', $data);

        return back()->with('success', 'Integrations saved.');
    }
}
