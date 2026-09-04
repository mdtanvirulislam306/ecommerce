<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Notifications\Services\ChannelPreferenceService;

class PushChannelController extends Controller
{
    public function edit(Request $request, ChannelPreferenceService $prefs): Response
    {
        return Inertia::render('Notifications/Channels/Index', [
            'channel' => 'push',
            'preferences' => $prefs->forUser((int) $request->user()->id),
        ]);
    }

    public function update(Request $request, ChannelPreferenceService $prefs): RedirectResponse
    {
        $data = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*.channel' => ['required', 'string'],
            'preferences.*.enabled' => ['boolean'],
        ]);
        $prefs->save((int) $request->user()->id, $data['preferences']);

        return back()->with('success', 'Channel preferences saved.');
    }
}
