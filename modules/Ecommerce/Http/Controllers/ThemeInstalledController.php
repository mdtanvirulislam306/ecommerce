<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Models\ThemeSetting;
use Modules\Ecommerce\Services\ThemeSettingService;

class ThemeInstalledController extends Controller
{
    public function index(ThemeSettingService $service): Response
    {
        return Inertia::render('Ecommerce/Theme/Installed', [
            'themes' => $service->installed(),
        ]);
    }

    public function activate(ThemeSetting $themeSetting, ThemeSettingService $service): RedirectResponse
    {
        $service->activate($themeSetting);

        return back()->with('success', 'Theme activated.');
    }
}
