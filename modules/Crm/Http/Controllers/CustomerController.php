<?php

namespace Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Crm\Http\Requests\StoreCustomerRequest;
use Modules\Crm\Http\Requests\UpdateCustomerRequest;
use Modules\Crm\Models\Customer;
use Modules\Crm\Services\ActivityService;
use Modules\Crm\Services\CustomerService;

class CustomerController extends Controller
{
    public function index(Request $request, CustomerService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();
        $isActive = $request->string('is_active')->toString();
        $groupId = $request->integer('customer_group_id') ?: null;
        $sort = $request->string('sort')->toString() ?: 'created_at';
        $direction = $request->string('direction')->toString() ?: 'desc';

        $filters = [
            'search' => $search ?: null,
            'is_active' => in_array($isActive, ['0', '1'], true) ? $isActive : null,
            'customer_group_id' => $groupId,
            'sort' => $sort,
            'direction' => $direction,
            'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
        ];

        return Inertia::render('Crm/Customers/Index', [
            'customers' => $service->listPaginated($filters),
            'groups' => $service->groupOptions(),
            'filters' => [
                'search' => $search,
                'is_active' => $filters['is_active'] ?? '',
                'customer_group_id' => $groupId,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $filters['per_page'],
            ],
            'openCreate' => $request->boolean('create') || $request->routeIs('crm.customers.create'),
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function create(Request $request, CustomerService $service): Response
    {
        $request->merge(['create' => true]);

        return $this->index($request, $service);
    }

    public function show(Customer $customer, CustomerService $service, ActivityService $activityService): Response
    {
        $profile = $service->profile($customer);

        return Inertia::render('Crm/Customers/Show', [
            ...$profile,
            'groups' => $service->groupOptions(),
            'activityTypeOptions' => $activityService->typeOptions(),
        ]);
    }

    public function store(StoreCustomerRequest $request, CustomerService $service): RedirectResponse
    {
        $service->create($request->validated(), $request->user()->id);

        return back()->with('success', 'Customer created.');
    }

    public function update(UpdateCustomerRequest $request, Customer $customer, CustomerService $service): RedirectResponse
    {
        $service->update($customer, $request->validated());

        return back()->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer, CustomerService $service): RedirectResponse
    {
        $service->delete($customer);

        return back()->with('success', 'Customer removed.');
    }
}
