<?php

namespace Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Crm\Enums\ActivityType;
use Modules\Crm\Http\Requests\StoreActivityRequest;
use Modules\Crm\Models\CrmActivity;
use Modules\Crm\Services\ActivityService;
use Modules\Crm\Services\CustomerService;
use Modules\Crm\Services\LeadService;

class ActivityController extends Controller
{
    public function index(Request $request, ActivityService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();
        $type = $request->filled('type')
            ? ActivityType::tryFrom($request->string('type')->toString())
            : null;
        $followUps = $request->routeIs('crm.activities.follow-ups')
            || $request->boolean('follow_ups');

        return Inertia::render('Crm/Activities/Index', [
            'activities' => $service->listPaginated($search ?: null, $type, $followUps, $perPage),
            'typeOptions' => $service->typeOptions(),
            'leadOptions' => app(LeadService::class)->listPaginated(perPage: 50)->items(),
            'customerOptions' => app(CustomerService::class)->listPaginated(perPage: 50)->items(),
            'filters' => [
                'search' => $search,
                'type' => $type?->value,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'listTitle' => $followUps ? 'Follow-ups' : 'All Activities',
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function calendar(Request $request, ActivityService $service): Response
    {
        return Inertia::render('Crm/Activities/Calendar', [
            'calendar' => $service->calendar($request->string('date')->toString() ?: null),
        ]);
    }

    public function store(StoreActivityRequest $request, ActivityService $service): RedirectResponse
    {
        $service->create($request->validated(), $request->user()->id);

        return back()->with('success', 'Activity logged.');
    }

    public function complete(CrmActivity $crmActivity, ActivityService $service): RedirectResponse
    {
        $service->complete($crmActivity);

        return back()->with('success', 'Activity completed.');
    }

    public function destroy(CrmActivity $crmActivity, ActivityService $service): RedirectResponse
    {
        $service->delete($crmActivity);

        return back()->with('success', 'Activity removed.');
    }
}
