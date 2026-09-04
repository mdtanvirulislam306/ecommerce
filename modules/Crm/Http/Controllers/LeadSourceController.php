<?php

namespace Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Crm\Http\Requests\StoreLeadSourceRequest;
use Modules\Crm\Http\Requests\UpdateLeadSourceRequest;
use Modules\Crm\Models\LeadSource;
use Modules\Crm\Services\LeadSourceService;

class LeadSourceController extends Controller
{
    public function index(Request $request, LeadSourceService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Crm/LeadSources/Index', [
            'sources' => $service->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreLeadSourceRequest $request, LeadSourceService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Lead source created.');
    }

    public function quickStore(StoreLeadSourceRequest $request, LeadSourceService $service): \Illuminate\Http\JsonResponse
    {
        $source = $service->create($request->validated());

        return response()->json([
            'item' => [
                'id' => $source->id,
                'name' => $source->name,
                'code' => $source->code,
            ],
        ]);
    }

    public function update(UpdateLeadSourceRequest $request, LeadSource $leadSource, LeadSourceService $service): RedirectResponse
    {
        $service->update($leadSource, $request->validated());

        return back()->with('success', 'Lead source updated.');
    }

    public function destroy(LeadSource $leadSource, LeadSourceService $service): RedirectResponse
    {
        $service->delete($leadSource);

        return back()->with('success', 'Lead source removed.');
    }
}
