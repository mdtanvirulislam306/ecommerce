<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Services\SettingService;

class BusinessProfileController extends Controller
{
    public function edit(SettingService $settings): Response
    {
        return Inertia::render('Settings/Business/Company', [
            'settings' => array_merge([
                'company_name' => '',
                'legal_name' => '',
                'email' => '',
                'phone' => '',
                'address' => '',
                'tax_id' => '',
            ], $settings->getGroup('business')),
        ]);
    }

    public function update(Request $request, SettingService $settings): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:1000'],
            'tax_id' => ['nullable', 'string', 'max:80'],
        ]);
        $settings->saveGroup('business', $data);

        return back()->with('success', 'Business profile saved.');
    }
}
