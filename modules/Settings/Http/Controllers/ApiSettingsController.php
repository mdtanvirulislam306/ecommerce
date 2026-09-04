<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Services\SettingService;

class ApiSettingsController extends Controller
{
    public function edit(SettingService $settings): Response
    {
        return Inertia::render('Settings/Api/Index', [
            'settings' => array_merge([
                'api_token' => '',
                'webhook_url' => '',
                'webhook_secret' => '',
            ], $settings->getGroup('api')),
        ]);
    }

    public function update(Request $request, SettingService $settings): RedirectResponse
    {
        $data = $request->validate([
            'api_token' => ['nullable', 'string', 'max:255'],
            'webhook_url' => ['nullable', 'url', 'max:500'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);
        $settings->saveGroup('api', $data);

        return back()->with('success', 'API settings saved.');
    }
}
