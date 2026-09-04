<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Http\Requests\StoreTaxRateRequest;
use Modules\Settings\Http\Requests\UpdateTaxRateRequest;
use Modules\Settings\Models\TaxRate;
use Modules\Settings\Services\TaxRateService;

class TaxRateController extends Controller
{
    public function index(Request $request, TaxRateService $svc): Response
    {
        return Inertia::render('Settings/Tax/Index', [
            'taxes' => $svc->listPaginated(
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

    public function store(StoreTaxRateRequest $request, TaxRateService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Saved.');
    }

    public function update(UpdateTaxRateRequest $request, TaxRate $taxRate, TaxRateService $svc): RedirectResponse
    {
        $svc->update($taxRate, $request->validated());

        return back()->with('success', 'Updated.');
    }

    public function destroy(TaxRate $taxRate, TaxRateService $svc): RedirectResponse
    {
        $svc->delete($taxRate);

        return back()->with('success', 'Deleted.');
    }
}
