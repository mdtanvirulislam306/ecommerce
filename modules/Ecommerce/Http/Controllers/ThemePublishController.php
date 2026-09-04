<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Models\ThemeSetting;
use Modules\Ecommerce\Services\ThemeSettingService;

class ThemePublishController extends Controller
{
    public function index(ThemeSettingService $service): Response
    {
        return Inertia::render('Ecommerce/Theme/Publish', [
            'activeTheme' => $service->active(),
            'publishedTheme' => ThemeSetting::query()
                ->where('is_published', true)
                ->first(),
        ]);
    }

    public function publish(ThemeSettingService $service): RedirectResponse
    {
        $service->publishActive();

        return back()->with('success', 'Theme published to storefront.');
    }
}
