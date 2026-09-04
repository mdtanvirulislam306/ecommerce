<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Accounting\Enums\AccountType;
use Modules\Accounting\Services\LedgerInquiryService;

class LedgerInquiryController extends Controller
{
    public function generalLedger(Request $request, LedgerInquiryService $service): Response
    {
        $accountId = $request->filled('ledger_account_id') ? (int) $request->input('ledger_account_id') : null;

        return Inertia::render('Accounting/GeneralLedger/Index', [
            'lines' => $service->generalLedger(
                $accountId,
                $request->string('from')->toString() ?: null,
                $request->string('to')->toString() ?: null,
                (int) $request->input('per_page', 50),
            ),
            'accounts' => $service->accountOptions(),
            'filters' => [
                'ledger_account_id' => $accountId,
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
                'per_page' => (int) $request->input('per_page', 50),
            ],
        ]);
    }

    public function cashbook(Request $request, LedgerInquiryService $service): Response
    {
        return Inertia::render('Accounting/Cashbook/Index', [
            'lines' => $service->cashbook(
                $request->string('from')->toString() ?: null,
                $request->string('to')->toString() ?: null,
                (int) $request->input('per_page', 50),
            ),
            'filters' => [
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
                'per_page' => (int) $request->input('per_page', 50),
            ],
        ]);
    }

    public function receivables(LedgerInquiryService $service): Response
    {
        return Inertia::render('Accounting/Receivables/Index', $service->receivables());
    }

    public function payables(LedgerInquiryService $service): Response
    {
        return Inertia::render('Accounting/Payables/Index', $service->payables());
    }

    public function expenses(LedgerInquiryService $service): Response
    {
        return Inertia::render('Accounting/Expenses/Index', $service->accountTypeSummary(AccountType::Expense));
    }

    public function income(LedgerInquiryService $service): Response
    {
        return Inertia::render('Accounting/Income/Index', $service->accountTypeSummary(AccountType::Income));
    }

    public function profitLoss(LedgerInquiryService $service): Response
    {
        return Inertia::render('Accounting/Reports/ProfitLoss', $service->profitAndLoss());
    }

    public function balanceSheet(LedgerInquiryService $service): Response
    {
        return Inertia::render('Accounting/Reports/BalanceSheet', $service->balanceSheet());
    }

    public function cashFlow(LedgerInquiryService $service): Response
    {
        return Inertia::render('Accounting/Reports/CashFlow', $service->cashFlow());
    }

    public function generalLedgerReport(Request $request, LedgerInquiryService $service): Response
    {
        return $this->generalLedger($request, $service);
    }
}
