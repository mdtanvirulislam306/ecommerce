<?php

namespace Modules\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Workflow\Http\Requests\StoreAutomationRuleRequest;
use Modules\Workflow\Http\Requests\UpdateAutomationRuleRequest;
use Modules\Workflow\Models\AutomationRule;
use Modules\Workflow\Services\AutomationRuleService;

class AutomationRuleController extends Controller
{
    public function index(Request $request, AutomationRuleService $svc): Response
    {
        return Inertia::render('Workflow/Automation/Index', [
            'rules' => $svc->listPaginated(
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

    public function store(StoreAutomationRuleRequest $request, AutomationRuleService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Saved.');
    }

    public function update(UpdateAutomationRuleRequest $request, AutomationRule $automationRule, AutomationRuleService $svc): RedirectResponse
    {
        $svc->update($automationRule, $request->validated());

        return back()->with('success', 'Updated.');
    }

    public function destroy(AutomationRule $automationRule, AutomationRuleService $svc): RedirectResponse
    {
        $svc->delete($automationRule);

        return back()->with('success', 'Deleted.');
    }
}
