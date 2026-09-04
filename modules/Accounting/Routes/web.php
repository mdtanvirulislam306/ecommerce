<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\AccountingMasterController;
use Modules\Accounting\Http\Controllers\AccountingOverviewController;
use Modules\Accounting\Http\Controllers\JournalEntryController;
use Modules\Accounting\Http\Controllers\LedgerAccountController;
use Modules\Accounting\Http\Controllers\LedgerInquiryController;
use Modules\Accounting\Http\Controllers\TrialBalanceController;

Route::get('/overview', [AccountingOverviewController::class, 'index'])->name('overview');

Route::prefix('accounts')->name('accounts.')->group(function () {
    Route::get('/tree', [LedgerAccountController::class, 'index'])->name('tree');
    Route::get('/assets', [LedgerAccountController::class, 'index'])->name('assets');
    Route::get('/liabilities', [LedgerAccountController::class, 'index'])->name('liabilities');
    Route::get('/equity', [LedgerAccountController::class, 'index'])->name('equity');
    Route::get('/income', [LedgerAccountController::class, 'index'])->name('income');
    Route::get('/expenses', [LedgerAccountController::class, 'index'])->name('expenses');
    Route::post('/', [LedgerAccountController::class, 'store'])->name('store');
    Route::put('/{ledgerAccount}', [LedgerAccountController::class, 'update'])->name('update');
    Route::delete('/{ledgerAccount}', [LedgerAccountController::class, 'destroy'])->name('destroy');
});

Route::prefix('journal-entries')->name('journal-entries.')->group(function () {
    Route::get('/', [JournalEntryController::class, 'index'])->name('index');
    Route::get('/create', [JournalEntryController::class, 'create'])->name('create');
    Route::post('/', [JournalEntryController::class, 'store'])->name('store');
    Route::get('/{journalEntry}', [JournalEntryController::class, 'show'])->name('show');
});

Route::get('/general-ledger', [LedgerInquiryController::class, 'generalLedger'])->name('general-ledger');
Route::get('/cashbook', [LedgerInquiryController::class, 'cashbook'])->name('cashbook');
Route::get('/receivables', [LedgerInquiryController::class, 'receivables'])->name('receivables');
Route::get('/payables', [LedgerInquiryController::class, 'payables'])->name('payables');
Route::get('/expenses', [LedgerInquiryController::class, 'expenses'])->name('expenses');
Route::get('/income', [LedgerInquiryController::class, 'income'])->name('income');

Route::get('/bank-accounts', [AccountingMasterController::class, 'bankAccounts'])->name('bank-accounts');
Route::post('/bank-accounts', [AccountingMasterController::class, 'storeBankAccount'])->name('bank-accounts.store');
Route::put('/bank-accounts/{bankAccount}', [AccountingMasterController::class, 'updateBankAccount'])->name('bank-accounts.update');
Route::delete('/bank-accounts/{bankAccount}', [AccountingMasterController::class, 'destroyBankAccount'])->name('bank-accounts.destroy');

Route::get('/bank-transactions', [AccountingMasterController::class, 'bankTransactions'])->name('bank-transactions');
Route::post('/bank-transactions', [AccountingMasterController::class, 'storeBankTransaction'])->name('bank-transactions.store');
Route::put('/bank-transactions/{bankTransaction}', [AccountingMasterController::class, 'updateBankTransaction'])->name('bank-transactions.update');
Route::delete('/bank-transactions/{bankTransaction}', [AccountingMasterController::class, 'destroyBankTransaction'])->name('bank-transactions.destroy');

Route::get('/bank-reconciliation', [AccountingMasterController::class, 'bankReconciliation'])->name('bank-reconciliation');
Route::post('/bank-reconciliation/{bankTransaction}', [AccountingMasterController::class, 'reconcileBankTransaction'])->name('bank-reconciliation.reconcile');

Route::get('/cost-centers', [AccountingMasterController::class, 'costCenters'])->name('cost-centers');
Route::post('/cost-centers', [AccountingMasterController::class, 'storeCostCenter'])->name('cost-centers.store');
Route::put('/cost-centers/{costCenter}', [AccountingMasterController::class, 'updateCostCenter'])->name('cost-centers.update');
Route::delete('/cost-centers/{costCenter}', [AccountingMasterController::class, 'destroyCostCenter'])->name('cost-centers.destroy');

Route::get('/profit-centers', [AccountingMasterController::class, 'profitCenters'])->name('profit-centers');
Route::post('/profit-centers', [AccountingMasterController::class, 'storeProfitCenter'])->name('profit-centers.store');
Route::put('/profit-centers/{profitCenter}', [AccountingMasterController::class, 'updateProfitCenter'])->name('profit-centers.update');
Route::delete('/profit-centers/{profitCenter}', [AccountingMasterController::class, 'destroyProfitCenter'])->name('profit-centers.destroy');

Route::get('/budgets', [AccountingMasterController::class, 'budgets'])->name('budgets');
Route::post('/budgets', [AccountingMasterController::class, 'storeBudget'])->name('budgets.store');
Route::put('/budgets/{budget}', [AccountingMasterController::class, 'updateBudget'])->name('budgets.update');
Route::delete('/budgets/{budget}', [AccountingMasterController::class, 'destroyBudget'])->name('budgets.destroy');

Route::get('/fixed-assets/assets', [AccountingMasterController::class, 'fixedAssets'])->name('fixed-assets.assets');
Route::post('/fixed-assets/assets', [AccountingMasterController::class, 'storeFixedAsset'])->name('fixed-assets.assets.store');
Route::put('/fixed-assets/assets/{fixedAsset}', [AccountingMasterController::class, 'updateFixedAsset'])->name('fixed-assets.assets.update');
Route::delete('/fixed-assets/assets/{fixedAsset}', [AccountingMasterController::class, 'destroyFixedAsset'])->name('fixed-assets.assets.destroy');

Route::get('/fixed-assets/categories', [AccountingMasterController::class, 'fixedAssetCategories'])->name('fixed-assets.categories');
Route::post('/fixed-assets/categories', [AccountingMasterController::class, 'storeFixedAssetCategory'])->name('fixed-assets.categories.store');
Route::put('/fixed-assets/categories/{fixedAssetCategory}', [AccountingMasterController::class, 'updateFixedAssetCategory'])->name('fixed-assets.categories.update');
Route::delete('/fixed-assets/categories/{fixedAssetCategory}', [AccountingMasterController::class, 'destroyFixedAssetCategory'])->name('fixed-assets.categories.destroy');

Route::get('/fixed-assets/depreciation', [AccountingMasterController::class, 'depreciations'])->name('fixed-assets.depreciation');
Route::post('/fixed-assets/depreciation', [AccountingMasterController::class, 'storeDepreciation'])->name('fixed-assets.depreciation.store');

Route::get('/fiscal-years', [AccountingMasterController::class, 'fiscalYears'])->name('fiscal-years');
Route::post('/fiscal-years', [AccountingMasterController::class, 'storeFiscalYear'])->name('fiscal-years.store');
Route::put('/fiscal-years/{fiscalYear}', [AccountingMasterController::class, 'updateFiscalYear'])->name('fiscal-years.update');
Route::delete('/fiscal-years/{fiscalYear}', [AccountingMasterController::class, 'destroyFiscalYear'])->name('fiscal-years.destroy');

Route::get('/financial-closing', [AccountingMasterController::class, 'financialClosing'])->name('financial-closing');
Route::post('/financial-closing', [AccountingMasterController::class, 'storeFinancialClosing'])->name('financial-closing.store');

Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/trial-balance', [TrialBalanceController::class, 'index'])->name('trial-balance');
    Route::get('/profit-loss', [LedgerInquiryController::class, 'profitLoss'])->name('profit-loss');
    Route::get('/balance-sheet', [LedgerInquiryController::class, 'balanceSheet'])->name('balance-sheet');
    Route::get('/cash-flow', [LedgerInquiryController::class, 'cashFlow'])->name('cash-flow');
    Route::get('/general-ledger', [LedgerInquiryController::class, 'generalLedgerReport'])->name('general-ledger');
});
