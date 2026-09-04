<?php

namespace Modules\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Workflow\Http\Requests\StoreWorkflowDefinitionRequest;
use Modules\Workflow\Http\Requests\UpdateWorkflowDefinitionRequest;
use Modules\Workflow\Models\WorkflowDefinition;
use Modules\Workflow\Services\WorkflowDefinitionService;

class WorkflowDefinitionController extends Controller
{
    public function index(Request $request, WorkflowDefinitionService $svc): Response
    {
        return Inertia::render('Workflow/Workflows/Index', [
            'workflows' => $svc->listPaginated(
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

    public function store(StoreWorkflowDefinitionRequest $request, WorkflowDefinitionService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Saved.');
    }

    public function update(UpdateWorkflowDefinitionRequest $request, WorkflowDefinition $workflowDefinition, WorkflowDefinitionService $svc): RedirectResponse
    {
        $svc->update($workflowDefinition, $request->validated());

        return back()->with('success', 'Updated.');
    }

    public function destroy(WorkflowDefinition $workflowDefinition, WorkflowDefinitionService $svc): RedirectResponse
    {
        $svc->delete($workflowDefinition);

        return back()->with('success', 'Deleted.');
    }
}
