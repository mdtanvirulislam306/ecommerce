<?php

namespace Modules\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Workflow\Http\Requests\StoreApprovalRequestRequest;
use Modules\Workflow\Http\Requests\UpdateApprovalRequestRequest;
use Modules\Workflow\Models\ApprovalRequest;
use Modules\Workflow\Services\ApprovalRequestService;

class ApprovalRequestController extends Controller
{
    public function index(Request $request, ApprovalRequestService $svc): Response
    {
        return Inertia::render('Workflow/ApprovalRequests/Index', [
            'requests' => $svc->listPaginated(
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

    public function store(StoreApprovalRequestRequest $request, ApprovalRequestService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Saved.');
    }

    public function update(UpdateApprovalRequestRequest $request, ApprovalRequest $approvalRequest, ApprovalRequestService $svc): RedirectResponse
    {
        $svc->update($approvalRequest, $request->validated());

        return back()->with('success', 'Updated.');
    }

    public function destroy(ApprovalRequest $approvalRequest, ApprovalRequestService $svc): RedirectResponse
    {
        $svc->delete($approvalRequest);

        return back()->with('success', 'Deleted.');
    }
}
