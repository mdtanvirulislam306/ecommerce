<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Http\Requests\InstallThemeRequest;
use Modules\Ecommerce\Models\ThemeSetting;
use Modules\Ecommerce\Services\ThemeSettingService;

class ThemeLibraryController extends Controller
{
    public function index(ThemeSettingService $service): Response
    {
        $installedCodes = ThemeSetting::query()
            ->where('is_installed', true)
            ->pluck('code')
            ->all();

        return Inertia::render('Ecommerce/Theme/Library', [
            'presets' => $service->libraryPresets(),
            'installedCodes' => $installedCodes,
        ]);
    }

    public function install(InstallThemeRequest $request, ThemeSettingService $service): RedirectResponse
    {
        $service->install($request->validated('code'));

        return back()->with('success', 'Theme installed.');
    }
}
