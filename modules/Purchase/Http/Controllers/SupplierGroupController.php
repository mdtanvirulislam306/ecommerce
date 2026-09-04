<?php

namespace Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Purchase\Http\Requests\StoreSupplierGroupRequest;
use Modules\Purchase\Http\Requests\UpdateSupplierGroupRequest;
use Modules\Purchase\Models\SupplierGroup;
use Modules\Purchase\Services\SupplierGroupService;

class SupplierGroupController extends Controller
{
    public function index(Request $request, SupplierGroupService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Purchase/SupplierGroups/Index', [
            'groups' => $service->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreSupplierGroupRequest $request, SupplierGroupService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Supplier group created.');
    }

    public function update(UpdateSupplierGroupRequest $request, SupplierGroup $supplierGroup, SupplierGroupService $service): RedirectResponse
    {
        $service->update($supplierGroup, $request->validated());

        return back()->with('success', 'Supplier group updated.');
    }

    public function destroy(SupplierGroup $supplierGroup, SupplierGroupService $service): RedirectResponse
    {
        $service->delete($supplierGroup);

        return back()->with('success', 'Supplier group removed.');
    }
}
