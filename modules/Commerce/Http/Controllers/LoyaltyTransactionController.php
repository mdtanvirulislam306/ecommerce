<?php

namespace Modules\Commerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Commerce\Http\Requests\StoreLoyaltyTransactionRequest;
use Modules\Commerce\Http\Requests\UpdateLoyaltyTransactionRequest;
use Modules\Commerce\Models\LoyaltyTransaction;
use Modules\Commerce\Services\LoyaltyPointService;
use Modules\Commerce\Services\LoyaltyTransactionService;

class LoyaltyTransactionController extends Controller
{
    public function index(Request $request, LoyaltyTransactionService $service, LoyaltyPointService $pointService): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Commerce/Loyalty/Transactions', [
            'transactions' => $service->listPaginated($search ?: null, $perPage),
            'loyaltyAccounts' => $pointService->activeAccounts(),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreLoyaltyTransactionRequest $request, LoyaltyTransactionService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Loyalty transaction recorded.');
    }

    public function update(UpdateLoyaltyTransactionRequest $request, LoyaltyTransaction $loyaltyTransaction, LoyaltyTransactionService $service): RedirectResponse
    {
        $service->update($loyaltyTransaction, $request->validated());

        return back()->with('success', 'Loyalty transaction updated.');
    }

    public function destroy(LoyaltyTransaction $loyaltyTransaction, LoyaltyTransactionService $service): RedirectResponse
    {
        $service->delete($loyaltyTransaction);

        return back()->with('success', 'Loyalty transaction removed.');
    }
}
