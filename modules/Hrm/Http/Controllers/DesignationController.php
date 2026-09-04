<?php

namespace Modules\Hrm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Hrm\Http\Requests\StoreDesignationRequest;
use Modules\Hrm\Http\Requests\UpdateDesignationRequest;
use Modules\Hrm\Models\Designation;
use Modules\Hrm\Services\DesignationService;

class DesignationController extends Controller
{
    public function index(Request $request, DesignationService $svc): Response
    {
        return Inertia::render('Hrm/Designations/Index', [
            'designations' => $svc->listPaginated(
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

    public function store(StoreDesignationRequest $request, DesignationService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Designation created.');
    }

    public function update(UpdateDesignationRequest $request, Designation $designation, DesignationService $svc): RedirectResponse
    {
        $svc->update($designation, $request->validated());

        return back()->with('success', 'Designation updated.');
    }

    public function destroy(Designation $designation, DesignationService $svc): RedirectResponse
    {
        $svc->delete($designation);

        return back()->with('success', 'Designation deleted.');
    }
}
