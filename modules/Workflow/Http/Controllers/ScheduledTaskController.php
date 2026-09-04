<?php

namespace Modules\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Workflow\Http\Requests\StoreScheduledTaskRequest;
use Modules\Workflow\Http\Requests\UpdateScheduledTaskRequest;
use Modules\Workflow\Models\ScheduledTask;
use Modules\Workflow\Services\ScheduledTaskService;

class ScheduledTaskController extends Controller
{
    public function index(Request $request, ScheduledTaskService $svc): Response
    {
        return Inertia::render('Workflow/ScheduledTasks/Index', [
            'tasks' => $svc->listPaginated(
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

    public function store(StoreScheduledTaskRequest $request, ScheduledTaskService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Saved.');
    }

    public function update(UpdateScheduledTaskRequest $request, ScheduledTask $scheduledTask, ScheduledTaskService $svc): RedirectResponse
    {
        $svc->update($scheduledTask, $request->validated());

        return back()->with('success', 'Updated.');
    }

    public function destroy(ScheduledTask $scheduledTask, ScheduledTaskService $svc): RedirectResponse
    {
        $svc->delete($scheduledTask);

        return back()->with('success', 'Deleted.');
    }
}
