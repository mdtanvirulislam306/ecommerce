<?php

namespace Modules\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Workflow\Http\Requests\StoreApprovalPolicyRequest;
use Modules\Workflow\Http\Requests\UpdateApprovalPolicyRequest;
use Modules\Workflow\Models\ApprovalPolicy;
use Modules\Workflow\Services\ApprovalPolicyService;

class ApprovalPolicyController extends Controller
{
    public function index(Request $request, ApprovalPolicyService $svc): Response
    {
        return Inertia::render('Workflow/ApprovalPolicies/Index', [
            'policies' => $svc->listPaginated(
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

    public function store(StoreApprovalPolicyRequest $request, ApprovalPolicyService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Saved.');
    }

    public function update(UpdateApprovalPolicyRequest $request, ApprovalPolicy $approvalPolicy, ApprovalPolicyService $svc): RedirectResponse
    {
        $svc->update($approvalPolicy, $request->validated());

        return back()->with('success', 'Updated.');
    }

    public function destroy(ApprovalPolicy $approvalPolicy, ApprovalPolicyService $svc): RedirectResponse
    {
        $svc->delete($approvalPolicy);

        return back()->with('success', 'Deleted.');
    }
}
