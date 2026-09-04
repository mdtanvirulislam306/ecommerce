<?php

namespace Modules\Commerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Commerce\Http\Requests\StoreCustomerWalletRequest;
use Modules\Commerce\Http\Requests\UpdateCustomerWalletRequest;
use Modules\Commerce\Models\CustomerWallet;
use Modules\Commerce\Services\CustomerWalletService;

class CustomerWalletController extends Controller
{
    public function index(Request $request, CustomerWalletService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Commerce/Wallets/Index', [
            'wallets' => $service->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreCustomerWalletRequest $request, CustomerWalletService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Customer wallet created.');
    }

    public function update(UpdateCustomerWalletRequest $request, CustomerWallet $customerWallet, CustomerWalletService $service): RedirectResponse
    {
        $service->update($customerWallet, $request->validated());

        return back()->with('success', 'Customer wallet updated.');
    }

    public function destroy(CustomerWallet $customerWallet, CustomerWalletService $service): RedirectResponse
    {
        $service->delete($customerWallet);

        return back()->with('success', 'Customer wallet removed.');
    }
}
