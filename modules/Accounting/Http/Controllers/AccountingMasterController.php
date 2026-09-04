<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Accounting\Models\BankAccount;
use Modules\Accounting\Models\BankTransaction;
use Modules\Accounting\Models\Budget;
use Modules\Accounting\Models\CostCenter;
use Modules\Accounting\Models\FinancialClosing;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Models\FixedAsset;
use Modules\Accounting\Models\FixedAssetCategory;
use Modules\Accounting\Models\FixedAssetDepreciation;
use Modules\Accounting\Models\ProfitCenter;
use Modules\Accounting\Services\AccountingMasterService;
use Modules\Accounting\Services\LedgerInquiryService;

class AccountingMasterController extends Controller
{
    public function bankAccounts(Request $request, AccountingMasterService $service): Response
    {
        return Inertia::render('Accounting/BankAccounts/Index', [
            'rows' => $service->paginate(BankAccount::class, $request->string('search')->toString() ?: null, (int) $request->input('per_page', 25)),
            'filters' => ['search' => $request->string('search')->toString(), 'per_page' => (int) $request->input('per_page', 25)],
        ]);
    }

    public function storeBankAccount(Request $request, AccountingMasterService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', 'unique:bank_accounts,code'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:80'],
            'currency' => ['nullable', 'string', 'max:3'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_active' => ['boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $service->createBankAccount($data);

        return back()->with('success', 'Bank account created.');
    }

    public function updateBankAccount(Request $request, BankAccount $bankAccount, AccountingMasterService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', Rule::unique('bank_accounts', 'code')->ignore($bankAccount->id)],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:80'],
            'currency' => ['nullable', 'string', 'max:3'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_active' => ['boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $service->updateBankAccount($bankAccount, $data);

        return back()->with('success', 'Bank account updated.');
    }

    public function destroyBankAccount(BankAccount $bankAccount, AccountingMasterService $service): RedirectResponse
    {
        $service->deleteBankAccount($bankAccount);

        return back()->with('success', 'Bank account removed.');
    }

    public function bankTransactions(Request $request, AccountingMasterService $service): Response
    {
        return Inertia::render('Accounting/BankTransactions/Index', [
            'rows' => $service->paginate(
                BankTransaction::class,
                null,
                (int) $request->input('per_page', 25),
                ['account:id,name,code'],
                fn (BankTransaction $txn) => [
                    'id' => $txn->id,
                    'bank_account_id' => $txn->bank_account_id,
                    'account_name' => $txn->account?->name,
                    'txn_date' => $txn->txn_date?->toDateString(),
                    'type' => $txn->type,
                    'amount' => (string) $txn->amount,
                    'reference' => $txn->reference,
                    'memo' => $txn->memo,
                    'is_reconciled' => $txn->is_reconciled,
                ],
            ),
            'accounts' => $service->bankAccountOptions(),
            'filters' => ['per_page' => (int) $request->input('per_page', 25)],
        ]);
    }

    public function storeBankTransaction(Request $request, AccountingMasterService $service): RedirectResponse
    {
        $data = $request->validate([
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'txn_date' => ['required', 'date'],
            'type' => ['required', Rule::in(['deposit', 'withdrawal', 'transfer'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:80'],
            'memo' => ['nullable', 'string', 'max:255'],
            'is_reconciled' => ['boolean'],
        ]);
        $data['is_reconciled'] = $request->boolean('is_reconciled');
        $service->createBankTransaction($data);

        return back()->with('success', 'Bank transaction recorded.');
    }

    public function updateBankTransaction(Request $request, BankTransaction $bankTransaction, AccountingMasterService $service): RedirectResponse
    {
        $data = $request->validate([
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'txn_date' => ['required', 'date'],
            'type' => ['required', Rule::in(['deposit', 'withdrawal', 'transfer'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:80'],
            'memo' => ['nullable', 'string', 'max:255'],
            'is_reconciled' => ['boolean'],
        ]);
        $data['is_reconciled'] = $request->boolean('is_reconciled');
        $service->updateBankTransaction($bankTransaction, $data);

        return back()->with('success', 'Bank transaction updated.');
    }

    public function destroyBankTransaction(BankTransaction $bankTransaction, AccountingMasterService $service): RedirectResponse
    {
        $service->deleteBankTransaction($bankTransaction);

        return back()->with('success', 'Bank transaction removed.');
    }

    public function bankReconciliation(Request $request, AccountingMasterService $service): Response
    {
        $rows = BankTransaction::query()
            ->with('account:id,name')
            ->where('is_reconciled', false)
            ->orderByDesc('txn_date')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (BankTransaction $txn) => [
                'id' => $txn->id,
                'account_name' => $txn->account?->name,
                'txn_date' => $txn->txn_date?->toDateString(),
                'type' => $txn->type,
                'amount' => (string) $txn->amount,
                'reference' => $txn->reference,
            ]);

        return Inertia::render('Accounting/BankReconciliation/Index', ['rows' => $rows]);
    }

    public function reconcileBankTransaction(BankTransaction $bankTransaction): RedirectResponse
    {
        $bankTransaction->update(['is_reconciled' => true]);

        return back()->with('success', 'Transaction marked reconciled.');
    }

    public function costCenters(Request $request, AccountingMasterService $service): Response
    {
        return Inertia::render('Accounting/CostCenters/Index', [
            'rows' => $service->paginate(CostCenter::class, $request->string('search')->toString() ?: null),
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function storeCostCenter(Request $request, AccountingMasterService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', 'unique:cost_centers,code'],
            'is_active' => ['boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $service->createNamed(CostCenter::class, $data);

        return back()->with('success', 'Cost center created.');
    }

    public function updateCostCenter(Request $request, CostCenter $costCenter, AccountingMasterService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', Rule::unique('cost_centers', 'code')->ignore($costCenter->id)],
            'is_active' => ['boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $service->updateNamed($costCenter, $data);

        return back()->with('success', 'Cost center updated.');
    }

    public function destroyCostCenter(CostCenter $costCenter, AccountingMasterService $service): RedirectResponse
    {
        $service->deleteNamed($costCenter);

        return back()->with('success', 'Cost center removed.');
    }

    public function profitCenters(Request $request, AccountingMasterService $service): Response
    {
        return Inertia::render('Accounting/ProfitCenters/Index', [
            'rows' => $service->paginate(ProfitCenter::class, $request->string('search')->toString() ?: null),
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function storeProfitCenter(Request $request, AccountingMasterService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', 'unique:profit_centers,code'],
            'is_active' => ['boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $service->createNamed(ProfitCenter::class, $data);

        return back()->with('success', 'Profit center created.');
    }

    public function updateProfitCenter(Request $request, ProfitCenter $profitCenter, AccountingMasterService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', Rule::unique('profit_centers', 'code')->ignore($profitCenter->id)],
            'is_active' => ['boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $service->updateNamed($profitCenter, $data);

        return back()->with('success', 'Profit center updated.');
    }

    public function destroyProfitCenter(ProfitCenter $profitCenter, AccountingMasterService $service): RedirectResponse
    {
        $service->deleteNamed($profitCenter);

        return back()->with('success', 'Profit center removed.');
    }

    public function budgets(Request $request, AccountingMasterService $service, LedgerInquiryService $ledger): Response
    {
        return Inertia::render('Accounting/Budgets/Index', [
            'rows' => $service->paginate(Budget::class, $request->string('search')->toString() ?: null, 25, ['account:id,code,name'], fn (Budget $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'period' => $b->period,
                'ledger_account_id' => $b->ledger_account_id,
                'account_name' => $b->account?->name,
                'amount' => (string) $b->amount,
                'notes' => $b->notes,
            ]),
            'accounts' => $ledger->accountOptions(),
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function storeBudget(Request $request, AccountingMasterService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'period' => ['required', 'string', 'max:20'],
            'ledger_account_id' => ['nullable', 'integer', 'exists:ledger_accounts,id'],
            'amount' => ['required', 'numeric'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
        $service->createBudget($data);

        return back()->with('success', 'Budget created.');
    }

    public function updateBudget(Request $request, Budget $budget, AccountingMasterService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'period' => ['required', 'string', 'max:20'],
            'ledger_account_id' => ['nullable', 'integer', 'exists:ledger_accounts,id'],
            'amount' => ['required', 'numeric'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
        $service->updateNamed($budget, $data);

        return back()->with('success', 'Budget updated.');
    }

    public function destroyBudget(Budget $budget, AccountingMasterService $service): RedirectResponse
    {
        $service->deleteNamed($budget);

        return back()->with('success', 'Budget removed.');
    }

    public function fixedAssetCategories(Request $request, AccountingMasterService $service): Response
    {
        return Inertia::render('Accounting/FixedAssets/Categories', [
            'rows' => $service->paginate(FixedAssetCategory::class, $request->string('search')->toString() ?: null),
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function storeFixedAssetCategory(Request $request, AccountingMasterService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', 'unique:fixed_asset_categories,code'],
            'useful_life_years' => ['nullable', 'numeric', 'min:0'],
            'depreciation_rate' => ['nullable', 'numeric', 'min:0'],
        ]);
        $service->createNamed(FixedAssetCategory::class, $data);

        return back()->with('success', 'Asset category created.');
    }

    public function updateFixedAssetCategory(Request $request, FixedAssetCategory $fixedAssetCategory, AccountingMasterService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', Rule::unique('fixed_asset_categories', 'code')->ignore($fixedAssetCategory->id)],
            'useful_life_years' => ['nullable', 'numeric', 'min:0'],
            'depreciation_rate' => ['nullable', 'numeric', 'min:0'],
        ]);
        $service->updateNamed($fixedAssetCategory, $data);

        return back()->with('success', 'Asset category updated.');
    }

    public function destroyFixedAssetCategory(FixedAssetCategory $fixedAssetCategory, AccountingMasterService $service): RedirectResponse
    {
        $service->deleteNamed($fixedAssetCategory);

        return back()->with('success', 'Asset category removed.');
    }

    public function fixedAssets(Request $request, AccountingMasterService $service): Response
    {
        return Inertia::render('Accounting/FixedAssets/Assets', [
            'rows' => $service->paginate(FixedAsset::class, $request->string('search')->toString() ?: null, 25, ['category:id,name'], fn (FixedAsset $a) => [
                'id' => $a->id,
                'name' => $a->name,
                'code' => $a->code,
                'fixed_asset_category_id' => $a->fixed_asset_category_id,
                'category_name' => $a->category?->name,
                'purchase_date' => $a->purchase_date?->toDateString(),
                'purchase_cost' => (string) $a->purchase_cost,
                'book_value' => (string) $a->book_value,
                'status' => $a->status,
            ]),
            'categories' => $service->assetCategoryOptions(),
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function storeFixedAsset(Request $request, AccountingMasterService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', 'unique:fixed_assets,code'],
            'fixed_asset_category_id' => ['nullable', 'integer', 'exists:fixed_asset_categories,id'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['required', 'numeric', 'min:0'],
            'book_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:30'],
        ]);
        $service->createFixedAsset($data);

        return back()->with('success', 'Fixed asset created.');
    }

    public function updateFixedAsset(Request $request, FixedAsset $fixedAsset, AccountingMasterService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', Rule::unique('fixed_assets', 'code')->ignore($fixedAsset->id)],
            'fixed_asset_category_id' => ['nullable', 'integer', 'exists:fixed_asset_categories,id'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['required', 'numeric', 'min:0'],
            'book_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:30'],
        ]);
        $service->updateNamed($fixedAsset, $data);

        return back()->with('success', 'Fixed asset updated.');
    }

    public function destroyFixedAsset(FixedAsset $fixedAsset, AccountingMasterService $service): RedirectResponse
    {
        $service->deleteNamed($fixedAsset);

        return back()->with('success', 'Fixed asset removed.');
    }

    public function depreciations(Request $request, AccountingMasterService $service): Response
    {
        return Inertia::render('Accounting/FixedAssets/Depreciation', [
            'rows' => $service->paginate(FixedAssetDepreciation::class, null, 25, ['asset:id,name,code'], fn (FixedAssetDepreciation $d) => [
                'id' => $d->id,
                'fixed_asset_id' => $d->fixed_asset_id,
                'asset_name' => $d->asset?->name,
                'period_date' => $d->period_date?->toDateString(),
                'amount' => (string) $d->amount,
                'notes' => $d->notes,
            ]),
            'assets' => $service->assetOptions(),
        ]);
    }

    public function storeDepreciation(Request $request, AccountingMasterService $service): RedirectResponse
    {
        $data = $request->validate([
            'fixed_asset_id' => ['required', 'integer', 'exists:fixed_assets,id'],
            'period_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
        $service->createDepreciation($data);

        return back()->with('success', 'Depreciation stub recorded.');
    }

    public function fiscalYears(Request $request, AccountingMasterService $service): Response
    {
        return Inertia::render('Accounting/FiscalYears/Index', [
            'rows' => $service->paginate(FiscalYear::class, $request->string('search')->toString() ?: null),
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function storeFiscalYear(Request $request, AccountingMasterService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'is_current' => ['boolean'],
        ]);
        $data['is_current'] = $request->boolean('is_current');
        $service->createFiscalYear($data);

        return back()->with('success', 'Fiscal year created.');
    }

    public function updateFiscalYear(Request $request, FiscalYear $fiscalYear, AccountingMasterService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'is_current' => ['boolean'],
        ]);
        $data['is_current'] = $request->boolean('is_current');
        if ($data['is_current']) {
            FiscalYear::query()->whereKeyNot($fiscalYear->id)->update(['is_current' => false]);
        }
        $service->updateNamed($fiscalYear, $data);

        return back()->with('success', 'Fiscal year updated.');
    }

    public function destroyFiscalYear(FiscalYear $fiscalYear, AccountingMasterService $service): RedirectResponse
    {
        $service->deleteNamed($fiscalYear);

        return back()->with('success', 'Fiscal year removed.');
    }

    public function financialClosing(AccountingMasterService $service): Response
    {
        $closings = FinancialClosing::query()
            ->with('fiscalYear:id,name')
            ->orderByDesc('closed_on')
            ->get()
            ->map(fn (FinancialClosing $c) => [
                'id' => $c->id,
                'fiscal_year' => $c->fiscalYear?->name,
                'closed_on' => $c->closed_on?->toDateString(),
                'notes' => $c->notes,
            ]);

        return Inertia::render('Accounting/FinancialClosing/Index', [
            'closings' => $closings,
            'years' => FiscalYear::query()->where('is_closed', false)->orderByDesc('starts_on')->get(['id', 'name']),
        ]);
    }

    public function storeFinancialClosing(Request $request, AccountingMasterService $service): RedirectResponse
    {
        $data = $request->validate([
            'fiscal_year_id' => ['required', 'integer', 'exists:fiscal_years,id'],
            'closed_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
        $service->closeFiscalYear($data, $request->user()->id);

        return back()->with('success', 'Financial year closed.');
    }
}
