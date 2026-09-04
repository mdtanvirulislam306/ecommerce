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
use Modules\Crm\Services\CustomerService;

class CustomerController extends Controller
{
    public function index(Request $request, CustomerService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Crm/Customers/Index', [
            'customers' => $service->listPaginated($search ?: null, $perPage),
            'groups' => $service->groupOptions(),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
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
