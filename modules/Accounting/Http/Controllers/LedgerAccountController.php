<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Accounting\Enums\AccountType;
use Modules\Accounting\Http\Requests\StoreLedgerAccountRequest;
use Modules\Accounting\Http\Requests\UpdateLedgerAccountRequest;
use Modules\Accounting\Models\LedgerAccount;
use Modules\Accounting\Services\ChartOfAccountsService;

class LedgerAccountController extends Controller
{
    public function index(Request $request, ChartOfAccountsService $service): Response
    {
        $type = $this->resolveType($request);

        return Inertia::render('Accounting/Accounts/Index', [
            'accounts' => $service->tree($type),
            'typeOptions' => collect(AccountType::cases())->map(fn (AccountType $case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
            'filters' => [
                'type' => $type?->value,
            ],
            'listTitle' => $this->resolveTitle($request, $type),
        ]);
    }

    public function store(StoreLedgerAccountRequest $request, ChartOfAccountsService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Account created.');
    }

    public function update(UpdateLedgerAccountRequest $request, LedgerAccount $ledgerAccount, ChartOfAccountsService $service): RedirectResponse
    {
        $service->update($ledgerAccount, $request->validated());

        return back()->with('success', 'Account updated.');
    }

    public function destroy(LedgerAccount $ledgerAccount, ChartOfAccountsService $service): RedirectResponse
    {
        $service->delete($ledgerAccount);

        return back()->with('success', 'Account removed.');
    }

    private function resolveType(Request $request): ?AccountType
    {
        return match (true) {
            $request->routeIs('accounting.accounts.assets') => AccountType::Asset,
            $request->routeIs('accounting.accounts.liabilities') => AccountType::Liability,
            $request->routeIs('accounting.accounts.equity') => AccountType::Equity,
            $request->routeIs('accounting.accounts.income') => AccountType::Income,
            $request->routeIs('accounting.accounts.expenses') => AccountType::Expense,
            $request->filled('type') => AccountType::tryFrom($request->string('type')->toString()),
            default => null,
        };
    }

    private function resolveTitle(Request $request, ?AccountType $type): string
    {
        if ($request->routeIs('accounting.accounts.tree') || $type === null) {
            return 'Chart of Accounts';
        }

        return $type->label().' Accounts';
    }
}
