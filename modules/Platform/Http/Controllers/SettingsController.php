<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Models\Plan;
use Modules\Platform\Http\Requests\SendTestEmailRequest;
use Modules\Platform\Http\Requests\UpdatePlatformSettingsRequest;
use Modules\Platform\Services\PlatformSettingService;
use Throwable;

class SettingsController extends Controller
{
    public function edit(PlatformSettingService $settings): Response
    {
        return Inertia::render('Platform/Settings/Index', [
            'settings' => $settings->forAdmin(),
            'plans' => Plan::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'price_monthly', 'currency']),
            'serverMailer' => (string) config('mail.default'),
        ]);
    }

    public function update(UpdatePlatformSettingsRequest $request, PlatformSettingService $settings): RedirectResponse
    {
        $settings->save($request->validated());

        return back()->with('success', 'Platform settings saved.');
    }

    public function sendTestEmail(SendTestEmailRequest $request, PlatformSettingService $settings): RedirectResponse
    {
        $validated = $request->validated();
        $platformName = $settings->get('platform_name');

        try {
            Mail::raw(
                "This is a test email from {$platformName}. If you can read this, platform email is working.",
                fn ($message) => $message->to($validated['email'])->subject("{$platformName} test email"),
            );
        } catch (Throwable $exception) {
            Log::warning('Platform test email failed', ['error' => $exception->getMessage()]);

            return back()->withErrors(['test_email' => 'The email could not be sent: '.$exception->getMessage()]);
        }

        return back()->with('success', "Test email sent to {$validated['email']}.");
    }
}
