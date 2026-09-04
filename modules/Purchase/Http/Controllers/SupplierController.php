<?php

namespace Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Purchase\Http\Requests\StoreSupplierRequest;
use Modules\Purchase\Http\Requests\UpdateSupplierRequest;
use Modules\Purchase\Models\Supplier;
use Modules\Purchase\Services\SupplierGroupService;
use Modules\Purchase\Services\SupplierService;

class SupplierController extends Controller
{
    public function index(Request $request, SupplierService $service, SupplierGroupService $groups): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Purchase/Suppliers/Index', [
            'suppliers' => $service->listPaginated($search ?: null, $perPage),
            'groups' => $groups->options(),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreSupplierRequest $request, SupplierService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Supplier created.');
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier, SupplierService $service): RedirectResponse
    {
        $service->update($supplier, $request->validated());

        return back()->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier, SupplierService $service): RedirectResponse
    {
        $service->delete($supplier);

        return back()->with('success', 'Supplier removed.');
    }
}
