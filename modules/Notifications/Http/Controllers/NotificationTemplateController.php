<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Notifications\Http\Requests\StoreNotificationTemplateRequest;
use Modules\Notifications\Http\Requests\UpdateNotificationTemplateRequest;
use Modules\Notifications\Models\NotificationTemplate;
use Modules\Notifications\Services\NotificationTemplateService;

class NotificationTemplateController extends Controller
{
    public function index(Request $request, NotificationTemplateService $svc): Response
    {
        return Inertia::render('Notifications/Templates/Index', [
            'templates' => $svc->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'per_page' => $request->integer('per_page', 25),
            ],
            'options' => method_exists($svc, 'formOptions') ? $svc->formOptions() : [],
        ]);
    }

    public function store(StoreNotificationTemplateRequest $request, NotificationTemplateService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Saved.');
    }

    public function update(UpdateNotificationTemplateRequest $request, NotificationTemplate $notificationTemplate, NotificationTemplateService $svc): RedirectResponse
    {
        $svc->update($notificationTemplate, $request->validated());

        return back()->with('success', 'Updated.');
    }

    public function destroy(NotificationTemplate $notificationTemplate, NotificationTemplateService $svc): RedirectResponse
    {
        $svc->delete($notificationTemplate);

        return back()->with('success', 'Deleted.');
    }
}
