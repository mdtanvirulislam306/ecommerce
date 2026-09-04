<?php

namespace Modules\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Workflow\Http\Requests\StoreWorkflowLogRequest;
use Modules\Workflow\Http\Requests\UpdateWorkflowLogRequest;
use Modules\Workflow\Models\WorkflowLog;
use Modules\Workflow\Services\WorkflowLogService;

class WorkflowLogController extends Controller
{
    public function index(Request $request, WorkflowLogService $svc): Response
    {
        return Inertia::render('Workflow/Logs/Index', [
            'logs' => $svc->listPaginated(
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

    public function store(StoreWorkflowLogRequest $request, WorkflowLogService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Saved.');
    }

    public function update(UpdateWorkflowLogRequest $request, WorkflowLog $workflowLog, WorkflowLogService $svc): RedirectResponse
    {
        $svc->update($workflowLog, $request->validated());

        return back()->with('success', 'Updated.');
    }

    public function destroy(WorkflowLog $workflowLog, WorkflowLogService $svc): RedirectResponse
    {
        $svc->delete($workflowLog);

        return back()->with('success', 'Deleted.');
    }
}
