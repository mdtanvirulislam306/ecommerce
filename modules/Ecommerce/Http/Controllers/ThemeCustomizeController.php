<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Http\Requests\UpdateThemeCustomizeRequest;
use Modules\Ecommerce\Services\ThemeSettingService;

class ThemeCustomizeController extends Controller
{
    public function index(ThemeSettingService $service): Response
    {
        $active = $service->active();
        $settings = $active?->settings ?? [
            'primary_color' => '#1e3a5f',
            'logo_url' => '',
            'header_html' => '',
        ];

        return Inertia::render('Ecommerce/Theme/Customize', [
            'theme' => $active,
            'settings' => $settings,
        ]);
    }

    public function update(UpdateThemeCustomizeRequest $request, ThemeSettingService $service): RedirectResponse
    {
        $active = $service->active();

        if ($active === null) {
            return back()->withErrors(['theme' => 'Install and activate a theme first.']);
        }

        $service->updateSettings($active, $request->validated());

        return back()->with('success', 'Theme settings saved.');
    }
}
