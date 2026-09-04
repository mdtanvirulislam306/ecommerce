<?php

namespace Modules\Commerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Commerce\Http\Requests\StoreCommissionRequest;
use Modules\Commerce\Http\Requests\UpdateCommissionRequest;
use Modules\Commerce\Models\Commission;
use Modules\Commerce\Services\CommissionService;

class CommissionController extends Controller
{
    public function index(Request $request, CommissionService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Commerce/Commissions/Index', [
            'commissions' => $service->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreCommissionRequest $request, CommissionService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Commission rule created.');
    }

    public function update(UpdateCommissionRequest $request, Commission $commission, CommissionService $service): RedirectResponse
    {
        $service->update($commission, $request->validated());

        return back()->with('success', 'Commission rule updated.');
    }

    public function destroy(Commission $commission, CommissionService $service): RedirectResponse
    {
        $service->delete($commission);

        return back()->with('success', 'Commission rule removed.');
    }
}
