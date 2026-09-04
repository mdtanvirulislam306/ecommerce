<?php

namespace Modules\Hrm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Hrm\Http\Requests\StoreSalaryStructureRequest;
use Modules\Hrm\Http\Requests\UpdateSalaryStructureRequest;
use Modules\Hrm\Models\SalaryStructure;
use Modules\Hrm\Services\SalaryStructureService;

class SalaryStructureController extends Controller
{
    public function index(Request $request, SalaryStructureService $svc): Response
    {
        return Inertia::render('Hrm/SalaryStructure/Index', [
            'structures' => $svc->listPaginated(
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

    public function store(StoreSalaryStructureRequest $request, SalaryStructureService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'SalaryStructure created.');
    }

    public function update(UpdateSalaryStructureRequest $request, SalaryStructure $salaryStructure, SalaryStructureService $svc): RedirectResponse
    {
        $svc->update($salaryStructure, $request->validated());

        return back()->with('success', 'SalaryStructure updated.');
    }

    public function destroy(SalaryStructure $salaryStructure, SalaryStructureService $svc): RedirectResponse
    {
        $svc->delete($salaryStructure);

        return back()->with('success', 'SalaryStructure deleted.');
    }
}
