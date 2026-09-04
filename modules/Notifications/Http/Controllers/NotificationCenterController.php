<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Notifications\Models\UserNotification;
use Modules\Notifications\Services\NotificationCenterService;

class NotificationCenterController extends Controller
{
    public function index(Request $request, NotificationCenterService $svc): Response
    {
        return Inertia::render('Notifications/Center/Index', [
            'notifications' => $svc->listForUser((int) $request->user()->id),
        ]);
    }

    public function markRead(UserNotification $userNotification, NotificationCenterService $svc): RedirectResponse
    {
        abort_unless($userNotification->user_id === auth()->id(), 403);
        $svc->markRead($userNotification);

        return back()->with('success', 'Marked as read.');
    }
}
