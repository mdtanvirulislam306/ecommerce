<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Services\SettingService;

class LocalizationController extends Controller
{
    public function edit(SettingService $settings): Response
    {
        return Inertia::render('Settings/Localization/Index', [
            'settings' => array_merge([
                'language' => 'en',
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i',
                'first_day_of_week' => '0',
            ], $settings->getGroup('localization')),
        ]);
    }

    public function update(Request $request, SettingService $settings): RedirectResponse
    {
        $data = $request->validate([
            'language' => ['nullable', 'string', 'max:20'],
            'date_format' => ['nullable', 'string', 'max:40'],
            'time_format' => ['nullable', 'string', 'max:40'],
            'first_day_of_week' => ['nullable', 'string', 'max:5'],
        ]);
        $settings->saveGroup('localization', $data);

        return back()->with('success', 'Localization saved.');
    }
}
