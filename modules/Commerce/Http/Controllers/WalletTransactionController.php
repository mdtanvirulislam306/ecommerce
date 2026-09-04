<?php

namespace Modules\Commerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Commerce\Http\Requests\StoreWalletTransactionRequest;
use Modules\Commerce\Http\Requests\UpdateWalletTransactionRequest;
use Modules\Commerce\Models\WalletTransaction;
use Modules\Commerce\Services\CustomerWalletService;
use Modules\Commerce\Services\WalletTransactionService;

class WalletTransactionController extends Controller
{
    public function index(Request $request, WalletTransactionService $service, CustomerWalletService $walletService): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Commerce/Wallets/Transactions', [
            'transactions' => $service->listPaginated($search ?: null, $perPage),
            'wallets' => $walletService->activeWallets(),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreWalletTransactionRequest $request, WalletTransactionService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Wallet transaction recorded.');
    }

    public function update(UpdateWalletTransactionRequest $request, WalletTransaction $walletTransaction, WalletTransactionService $service): RedirectResponse
    {
        $service->update($walletTransaction, $request->validated());

        return back()->with('success', 'Wallet transaction updated.');
    }

    public function destroy(WalletTransaction $walletTransaction, WalletTransactionService $service): RedirectResponse
    {
        $service->delete($walletTransaction);

        return back()->with('success', 'Wallet transaction removed.');
    }
}
