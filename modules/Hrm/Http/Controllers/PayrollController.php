<?php

namespace Modules\Hrm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Hrm\Http\Requests\StorePayrollRequest;
use Modules\Hrm\Http\Requests\UpdatePayrollRequest;
use Modules\Hrm\Models\Payroll;
use Modules\Hrm\Services\PayrollService;

class PayrollController extends Controller
{
    public function index(Request $request, PayrollService $svc): Response
    {
        return Inertia::render('Hrm/Payroll/Index', [
            'payrolls' => $svc->listPaginated(
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

    public function store(StorePayrollRequest $request, PayrollService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Payroll created.');
    }

    public function update(UpdatePayrollRequest $request, Payroll $payroll, PayrollService $svc): RedirectResponse
    {
        $svc->update($payroll, $request->validated());

        return back()->with('success', 'Payroll updated.');
    }

    public function destroy(Payroll $payroll, PayrollService $svc): RedirectResponse
    {
        $svc->delete($payroll);

        return back()->with('success', 'Payroll deleted.');
    }
}
