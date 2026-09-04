<?php

namespace Modules\Hrm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Hrm\Http\Requests\StoreAttendanceRequest;
use Modules\Hrm\Http\Requests\UpdateAttendanceRequest;
use Modules\Hrm\Models\Attendance;
use Modules\Hrm\Services\AttendanceService;

class AttendanceController extends Controller
{
    public function index(Request $request, AttendanceService $svc): Response
    {
        return Inertia::render('Hrm/Attendance/Index', [
            'attendances' => $svc->listPaginated(
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

    public function store(StoreAttendanceRequest $request, AttendanceService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Attendance created.');
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance, AttendanceService $svc): RedirectResponse
    {
        $svc->update($attendance, $request->validated());

        return back()->with('success', 'Attendance updated.');
    }

    public function destroy(Attendance $attendance, AttendanceService $svc): RedirectResponse
    {
        $svc->delete($attendance);

        return back()->with('success', 'Attendance deleted.');
    }
}
