<?php

namespace Modules\Hrm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Hrm\Http\Requests\StoreLeaveRequest;
use Modules\Hrm\Http\Requests\UpdateLeaveRequest;
use Modules\Hrm\Models\Leave;
use Modules\Hrm\Services\LeaveService;

class LeaveController extends Controller
{
    public function index(Request $request, LeaveService $svc): Response
    {
        return Inertia::render('Hrm/Leave/Index', [
            'leaves' => $svc->listPaginated(
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

    public function store(StoreLeaveRequest $request, LeaveService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Leave created.');
    }

    public function update(UpdateLeaveRequest $request, Leave $leave, LeaveService $svc): RedirectResponse
    {
        $svc->update($leave, $request->validated());

        return back()->with('success', 'Leave updated.');
    }

    public function destroy(Leave $leave, LeaveService $svc): RedirectResponse
    {
        $svc->delete($leave);

        return back()->with('success', 'Leave deleted.');
    }
}
