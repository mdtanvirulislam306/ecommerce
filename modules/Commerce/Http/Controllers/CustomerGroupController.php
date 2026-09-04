<?php

namespace Modules\Commerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Commerce\Http\Requests\StoreCustomerGroupRequest;
use Modules\Commerce\Http\Requests\UpdateCustomerGroupRequest;
use Modules\Commerce\Models\CustomerGroup;
use Modules\Commerce\Services\CustomerGroupService;

class CustomerGroupController extends Controller
{
    public function index(Request $request, CustomerGroupService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Commerce/CustomerGroups/Index', [
            'groups' => $service->listPaginated($search ?: null, $perPage),
            'priceLists' => $service->activePriceLists(),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreCustomerGroupRequest $request, CustomerGroupService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Customer group created.');
    }

    public function quickStore(StoreCustomerGroupRequest $request, CustomerGroupService $service): \Illuminate\Http\JsonResponse
    {
        $group = $service->create($request->validated());

        return response()->json([
            'item' => [
                'id' => $group->id,
                'name' => $group->name,
                'code' => $group->code,
            ],
        ]);
    }

    public function update(UpdateCustomerGroupRequest $request, CustomerGroup $customerGroup, CustomerGroupService $service): RedirectResponse
    {
        $service->update($customerGroup, $request->validated());

        return back()->with('success', 'Customer group updated.');
    }

    public function destroy(CustomerGroup $customerGroup, CustomerGroupService $service): RedirectResponse
    {
        $service->delete($customerGroup);

        return back()->with('success', 'Customer group removed.');
    }
}
