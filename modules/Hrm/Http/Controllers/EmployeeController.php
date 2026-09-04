<?php

namespace Modules\Hrm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Hrm\Http\Requests\StoreEmployeeRequest;
use Modules\Hrm\Http\Requests\UpdateEmployeeRequest;
use Modules\Hrm\Models\Employee;
use Modules\Hrm\Services\EmployeeService;

class EmployeeController extends Controller
{
    public function index(Request $request, EmployeeService $employees): Response
    {
        return Inertia::render('Hrm/Employees/Index', [
            'employees' => $employees->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'per_page' => $request->integer('per_page', 25),
            ],
        ]);
    }

    public function store(StoreEmployeeRequest $request, EmployeeService $employees): RedirectResponse
    {
        $employees->create($request->validated());

        return back()->with('success', 'Employee created.');
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee, EmployeeService $employees): RedirectResponse
    {
        $employees->update($employee, $request->validated());

        return back()->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee, EmployeeService $employees): RedirectResponse
    {
        $employees->delete($employee);

        return back()->with('success', 'Employee removed.');
    }
}
