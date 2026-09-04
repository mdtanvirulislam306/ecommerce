<?php

namespace Modules\Accounting\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Enums\AccountType;
use Modules\Accounting\Enums\JournalStatus;
use Modules\Accounting\Models\LedgerAccount;

class LedgerInquiryService extends Service
{
    public function generalLedger(?int $accountId = null, ?string $from = null, ?string $to = null, int $perPage = 50): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 50;

        return DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->where('journal_entries.status', JournalStatus::Posted->value)
            ->when($accountId, fn ($query, $accountId) => $query->where('journal_lines.ledger_account_id', $accountId))
            ->when($from, fn ($query, $from) => $query->whereDate('journal_entries.entry_date', '>=', $from))
            ->when($to, fn ($query, $to) => $query->whereDate('journal_entries.entry_date', '<=', $to))
            ->orderByDesc('journal_entries.entry_date')
            ->orderByDesc('journal_lines.id')
            ->select([
                'journal_lines.id',
                'journal_entries.number as entry_number',
                'journal_entries.entry_date',
                'journal_entries.memo as entry_memo',
                'ledger_accounts.code as account_code',
                'ledger_accounts.name as account_name',
                'journal_lines.debit',
                'journal_lines.credit',
                'journal_lines.memo',
            ])
            ->paginate($perPage)
            ->withQueryString();
    }

    public function cashbook(?string $from = null, ?string $to = null, int $perPage = 50): LengthAwarePaginator
    {
        $cashAccountIds = LedgerAccount::query()
            ->where('type', AccountType::Asset)
            ->where(function ($query) {
                $query->where('code', 'like', '10%')
                    ->orWhere('name', 'like', '%cash%')
                    ->orWhere('name', 'like', '%bank%');
            })
            ->pluck('id')
            ->all();

        $perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 50;

        return DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->where('journal_entries.status', JournalStatus::Posted->value)
            ->when($cashAccountIds !== [], fn ($query) => $query->whereIn('journal_lines.ledger_account_id', $cashAccountIds))
            ->when($from, fn ($query, $from) => $query->whereDate('journal_entries.entry_date', '>=', $from))
            ->when($to, fn ($query, $to) => $query->whereDate('journal_entries.entry_date', '<=', $to))
            ->orderByDesc('journal_entries.entry_date')
            ->select([
                'journal_lines.id',
                'journal_entries.number as entry_number',
                'journal_entries.entry_date',
                'ledger_accounts.code as account_code',
                'ledger_accounts.name as account_name',
                'journal_lines.debit',
                'journal_lines.credit',
                'journal_lines.memo',
            ])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: string}
     */
    public function accountTypeSummary(AccountType $type): array
    {
        $rows = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->where('journal_entries.status', JournalStatus::Posted->value)
            ->where('ledger_accounts.type', $type->value)
            ->selectRaw('ledger_accounts.id, ledger_accounts.code, ledger_accounts.name, SUM(journal_lines.debit) as debit, SUM(journal_lines.credit) as credit')
            ->groupBy('ledger_accounts.id', 'ledger_accounts.code', 'ledger_accounts.name')
            ->orderBy('ledger_accounts.code')
            ->get()
            ->map(function ($row) use ($type) {
                $debit = (float) $row->debit;
                $credit = (float) $row->credit;
                $balance = in_array($type, [AccountType::Asset, AccountType::Expense], true)
                    ? $debit - $credit
                    : $credit - $debit;

                return [
                    'id' => $row->id,
                    'code' => $row->code,
                    'name' => $row->name,
                    'debit' => number_format($debit, 4, '.', ''),
                    'credit' => number_format($credit, 4, '.', ''),
                    'balance' => number_format($balance, 4, '.', ''),
                ];
            })
            ->all();

        $total = collect($rows)->sum(fn ($row) => (float) $row['balance']);

        return [
            'rows' => $rows,
            'total' => number_format($total, 4, '.', ''),
        ];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: string}
     */
    public function receivables(): array
    {
        $orders = DB::table('sales_orders')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->selectRaw("id, number, COALESCE(customer_name, 'Customer') as party, grand_total as amount, status, created_at")
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $payments = \Illuminate\Support\Facades\Schema::hasTable('sales_payments')
            ? DB::table('sales_payments')->selectRaw('sales_order_id, SUM(amount) as paid')->groupBy('sales_order_id')->pluck('paid', 'sales_order_id')
            : collect();

        $rows = $orders->map(function ($row) use ($payments) {
            $paid = (float) ($payments[$row->id] ?? 0);
            $balance = max(0, (float) $row->amount - $paid);

            return [
                'number' => $row->number,
                'party' => $row->party,
                'amount' => number_format((float) $row->amount, 2, '.', ''),
                'paid' => number_format($paid, 2, '.', ''),
                'balance' => number_format($balance, 2, '.', ''),
                'status' => $row->status,
            ];
        })->all();

        return [
            'rows' => $rows,
            'total' => number_format(collect($rows)->sum(fn ($r) => (float) $r['balance']), 2, '.', ''),
        ];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: string}
     */
    public function payables(): array
    {
        $orders = DB::table('purchase_orders')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->whereNotIn('purchase_orders.status', ['draft', 'cancelled'])
            ->selectRaw('purchase_orders.id, purchase_orders.number, suppliers.name as party, purchase_orders.grand_total as amount, purchase_orders.status, purchase_orders.created_at')
            ->orderByDesc('purchase_orders.created_at')
            ->limit(100)
            ->get();

        $payments = \Illuminate\Support\Facades\Schema::hasTable('purchase_payments')
            ? DB::table('purchase_payments')->selectRaw('purchase_order_id, SUM(amount) as paid')->groupBy('purchase_order_id')->pluck('paid', 'purchase_order_id')
            : collect();

        $rows = $orders->map(function ($row) use ($payments) {
            $paid = (float) ($payments[$row->id] ?? 0);
            $balance = max(0, (float) $row->amount - $paid);

            return [
                'number' => $row->number,
                'party' => $row->party ?? 'Supplier',
                'amount' => number_format((float) $row->amount, 2, '.', ''),
                'paid' => number_format($paid, 2, '.', ''),
                'balance' => number_format($balance, 2, '.', ''),
                'status' => $row->status,
            ];
        })->all();

        return [
            'rows' => $rows,
            'total' => number_format(collect($rows)->sum(fn ($r) => (float) $r['balance']), 2, '.', ''),
        ];
    }

    /**
     * @return array{income: list<array<string,mixed>>, expense: list<array<string,mixed>>, net: string}
     */
    public function profitAndLoss(): array
    {
        $income = $this->accountTypeSummary(AccountType::Income);
        $expense = $this->accountTypeSummary(AccountType::Expense);
        $net = (float) $income['total'] - (float) $expense['total'];

        return [
            'income' => $income['rows'],
            'expense' => $expense['rows'],
            'income_total' => $income['total'],
            'expense_total' => $expense['total'],
            'net' => number_format($net, 4, '.', ''),
        ];
    }

    /**
     * @return array{assets: list<array<string,mixed>>, liabilities: list<array<string,mixed>>, equity: list<array<string,mixed>>, totals: array<string,string>}
     */
    public function balanceSheet(): array
    {
        $assets = $this->accountTypeSummary(AccountType::Asset);
        $liabilities = $this->accountTypeSummary(AccountType::Liability);
        $equity = $this->accountTypeSummary(AccountType::Equity);

        return [
            'assets' => $assets['rows'],
            'liabilities' => $liabilities['rows'],
            'equity' => $equity['rows'],
            'totals' => [
                'assets' => $assets['total'],
                'liabilities' => $liabilities['total'],
                'equity' => $equity['total'],
            ],
        ];
    }

    /**
     * Simplified cash flow from cash/bank journal activity.
     *
     * @return array{rows: list<array<string,mixed>>, net: string}
     */
    public function cashFlow(): array
    {
        $cashAccountIds = LedgerAccount::query()
            ->where('type', AccountType::Asset)
            ->where(function ($query) {
                $query->where('code', 'like', '10%')
                    ->orWhere('name', 'like', '%cash%')
                    ->orWhere('name', 'like', '%bank%');
            })
            ->pluck('id');

        $rows = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', JournalStatus::Posted->value)
            ->when($cashAccountIds->isNotEmpty(), fn ($query) => $query->whereIn('journal_lines.ledger_account_id', $cashAccountIds))
            ->selectRaw("DATE_FORMAT(journal_entries.entry_date, '%Y-%m') as period, SUM(journal_lines.debit) as inflow, SUM(journal_lines.credit) as outflow")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($row) {
                $in = (float) $row->inflow;
                $out = (float) $row->outflow;

                return [
                    'period' => $row->period,
                    'inflow' => number_format($in, 4, '.', ''),
                    'outflow' => number_format($out, 4, '.', ''),
                    'net' => number_format($in - $out, 4, '.', ''),
                ];
            })
            ->all();

        $net = collect($rows)->sum(fn ($row) => (float) $row['net']);

        return [
            'rows' => $rows,
            'net' => number_format($net, 4, '.', ''),
        ];
    }

    /**
     * @return list<array{id: int, code: string, name: string}>
     */
    public function accountOptions(): array
    {
        return LedgerAccount::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (LedgerAccount $account) => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
            ])
            ->all();
    }
}
