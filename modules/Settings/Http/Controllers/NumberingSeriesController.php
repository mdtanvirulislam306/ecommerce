<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Http\Requests\StoreNumberingSeriesRequest;
use Modules\Settings\Http\Requests\UpdateNumberingSeriesRequest;
use Modules\Settings\Models\NumberingSeries;
use Modules\Settings\Services\NumberingSeriesService;

class NumberingSeriesController extends Controller
{
    public function index(Request $request, NumberingSeriesService $svc): Response
    {
        return Inertia::render('Settings/Numbering/Index', [
            'series' => $svc->listPaginated(
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

    public function store(StoreNumberingSeriesRequest $request, NumberingSeriesService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Saved.');
    }

    public function update(UpdateNumberingSeriesRequest $request, NumberingSeries $numberingSeries, NumberingSeriesService $svc): RedirectResponse
    {
        $svc->update($numberingSeries, $request->validated());

        return back()->with('success', 'Updated.');
    }

    public function destroy(NumberingSeries $numberingSeries, NumberingSeriesService $svc): RedirectResponse
    {
        $svc->delete($numberingSeries);

        return back()->with('success', 'Deleted.');
    }
}
