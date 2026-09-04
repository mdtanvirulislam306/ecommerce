<?php

namespace Modules\Hrm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Hrm\Http\Requests\StoreDepartmentRequest;
use Modules\Hrm\Http\Requests\UpdateDepartmentRequest;
use Modules\Hrm\Models\Department;
use Modules\Hrm\Services\DepartmentService;

class DepartmentController extends Controller
{
    public function index(Request $request, DepartmentService $svc): Response
    {
        return Inertia::render('Hrm/Departments/Index', [
            'departments' => $svc->listPaginated(
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

    public function store(StoreDepartmentRequest $request, DepartmentService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Department created.');
    }

    public function update(UpdateDepartmentRequest $request, Department $department, DepartmentService $svc): RedirectResponse
    {
        $svc->update($department, $request->validated());

        return back()->with('success', 'Department updated.');
    }

    public function destroy(Department $department, DepartmentService $svc): RedirectResponse
    {
        $svc->delete($department);

        return back()->with('success', 'Department deleted.');
    }
}
