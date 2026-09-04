<?php

namespace Modules\Accounting\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\Enums\AccountType;
use Modules\Accounting\Enums\JournalStatus;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalLine;
use Modules\Accounting\Models\LedgerAccount;

class JournalService extends Service
{
    public function __construct(
        private readonly ChartOfAccountsService $accounts,
    ) {}

    /**
     * @return array{accounts: int, journals: int, posted_month: int, unbalanced: int}
     */
    public function overviewStats(): array
    {
        return [
            'accounts' => LedgerAccount::query()->where('is_active', true)->count(),
            'journals' => JournalEntry::query()->where('status', JournalStatus::Posted)->count(),
            'posted_month' => JournalEntry::query()
                ->where('status', JournalStatus::Posted)
                ->where('entry_date', '>=', now()->startOfMonth()->toDateString())
                ->count(),
            'draft' => JournalEntry::query()->where('status', JournalStatus::Draft)->count(),
        ];
    }

    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return JournalEntry::query()
            ->withCount('lines')
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhere('memo', 'like', "%{$search}%")
                    ->orWhere('source_type', 'like', "%{$search}%");
            }))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (JournalEntry $entry) => $this->formatList($entry));
    }

    /**
     * @param  array{
     *     entry_date: string,
     *     memo?: string|null,
     *     currency?: string,
     *     source_type?: string|null,
     *     source_id?: int|null,
     *     lines: list<array{ledger_account_id: int, debit?: float|int|string, credit?: float|int|string, memo?: string|null}>
     * }  $data
     */
    public function post(array $data, ?int $userId = null): JournalEntry
    {
        return DB::transaction(function () use ($data, $userId) {
            $lines = $this->normalizeLines($data['lines'] ?? []);
            $this->assertBalanced($lines);

            $entry = JournalEntry::query()->create([
                'number' => $this->nextNumber(),
                'entry_date' => $data['entry_date'],
                'status' => JournalStatus::Posted,
                'memo' => $data['memo'] ?? null,
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'currency' => $data['currency'] ?? 'BDT',
                'posted_at' => now(),
                'created_by' => $userId,
            ]);

            foreach ($lines as $index => $line) {
                $entry->lines()->create([
                    ...$line,
                    'sort_order' => $index,
                ]);
            }

            return $entry->fresh(['lines.account']);
        });
    }

    public function postSalesCredit(
        string $orderNumber,
        string $amount,
        string $currency,
        int $orderId,
        ?string $customerName = null,
        string $sourceType = 'sales_order_confirmed',
    ): ?JournalEntry {
        if ((float) $amount <= 0) {
            return null;
        }

        if ($this->alreadyPosted($sourceType, $orderId)) {
            return null;
        }

        $ar = $this->accounts->findByCode('1100');
        $revenue = $this->accounts->findByCode('4000');

        return $this->post([
            'entry_date' => now()->toDateString(),
            'memo' => 'Sale on credit '.$orderNumber.($customerName ? " — {$customerName}" : ''),
            'currency' => $currency,
            'source_type' => $sourceType,
            'source_id' => $orderId,
            'lines' => [
                ['ledger_account_id' => $ar->id, 'debit' => $amount, 'credit' => 0, 'memo' => 'AR'],
                ['ledger_account_id' => $revenue->id, 'debit' => 0, 'credit' => $amount, 'memo' => 'Sales revenue'],
            ],
        ]);
    }

    public function reverseSalesCredit(
        string $orderNumber,
        string $amount,
        string $currency,
        int $orderId,
        ?string $customerName = null,
        string $confirmSourceType = 'sales_order_confirmed',
        string $cancelSourceType = 'sales_order_cancelled',
    ): ?JournalEntry {
        if ((float) $amount <= 0) {
            return null;
        }

        if (! $this->alreadyPosted($confirmSourceType, $orderId)) {
            return null;
        }

        if ($this->alreadyPosted($cancelSourceType, $orderId)) {
            return null;
        }

        $ar = $this->accounts->findByCode('1100');
        $revenue = $this->accounts->findByCode('4000');

        return $this->post([
            'entry_date' => now()->toDateString(),
            'memo' => 'Reverse sale '.$orderNumber.($customerName ? " — {$customerName}" : ''),
            'currency' => $currency,
            'source_type' => $cancelSourceType,
            'source_id' => $orderId,
            'lines' => [
                ['ledger_account_id' => $revenue->id, 'debit' => $amount, 'credit' => 0, 'memo' => 'Reverse revenue'],
                ['ledger_account_id' => $ar->id, 'debit' => 0, 'credit' => $amount, 'memo' => 'Reverse AR'],
            ],
        ]);
    }

    public function postPurchaseReceive(string $orderNumber, string $amount, string $currency, int $orderId, ?string $supplierName = null): ?JournalEntry
    {
        if ((float) $amount <= 0) {
            return null;
        }

        $inventory = $this->accounts->findByCode('1200');
        $ap = $this->accounts->findByCode('2000');

        return $this->post([
            'entry_date' => now()->toDateString(),
            'memo' => 'Purchase receive '.$orderNumber.($supplierName ? " — {$supplierName}" : ''),
            'currency' => $currency,
            'source_type' => 'purchase_goods_received',
            'source_id' => $orderId,
            'lines' => [
                ['ledger_account_id' => $inventory->id, 'debit' => $amount, 'credit' => 0, 'memo' => 'Inventory'],
                ['ledger_account_id' => $ap->id, 'debit' => 0, 'credit' => $amount, 'memo' => 'AP'],
            ],
        ]);
    }

    public function postCashSale(
        string $orderNumber,
        string $amount,
        string $currency,
        int $orderId,
        ?string $customerName = null,
    ): ?JournalEntry {
        if ((float) $amount <= 0) {
            return null;
        }

        if ($this->alreadyPosted('pos_sale_completed', $orderId)) {
            return null;
        }

        $cash = $this->accounts->findByCode('1000');
        $revenue = $this->accounts->findByCode('4000');

        return $this->post([
            'entry_date' => now()->toDateString(),
            'memo' => 'POS cash sale '.$orderNumber.($customerName ? " — {$customerName}" : ''),
            'currency' => $currency,
            'source_type' => 'pos_sale_completed',
            'source_id' => $orderId,
            'lines' => [
                ['ledger_account_id' => $cash->id, 'debit' => $amount, 'credit' => 0, 'memo' => 'Cash'],
                ['ledger_account_id' => $revenue->id, 'debit' => 0, 'credit' => $amount, 'memo' => 'Sales revenue'],
            ],
        ]);
    }

    public function reverseCashSale(
        string $orderNumber,
        string $amount,
        string $currency,
        int $orderId,
        ?string $customerName = null,
    ): ?JournalEntry {
        if ((float) $amount <= 0) {
            return null;
        }

        if (! $this->alreadyPosted('pos_sale_completed', $orderId)) {
            return null;
        }

        if ($this->alreadyPosted('pos_sale_cancelled', $orderId)) {
            return null;
        }

        $cash = $this->accounts->findByCode('1000');
        $revenue = $this->accounts->findByCode('4000');

        return $this->post([
            'entry_date' => now()->toDateString(),
            'memo' => 'Reverse POS sale '.$orderNumber.($customerName ? " — {$customerName}" : ''),
            'currency' => $currency,
            'source_type' => 'pos_sale_cancelled',
            'source_id' => $orderId,
            'lines' => [
                ['ledger_account_id' => $revenue->id, 'debit' => $amount, 'credit' => 0, 'memo' => 'Reverse revenue'],
                ['ledger_account_id' => $cash->id, 'debit' => 0, 'credit' => $amount, 'memo' => 'Reverse cash'],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function formatDetail(JournalEntry $entry): array
    {
        $entry->loadMissing(['lines.account']);

        $debit = $entry->lines->sum(fn (JournalLine $line) => (float) $line->debit);
        $credit = $entry->lines->sum(fn (JournalLine $line) => (float) $line->credit);

        return [
            ...$this->formatList($entry),
            'source_type' => $entry->source_type,
            'source_id' => $entry->source_id,
            'posted_at' => $entry->posted_at?->toIso8601String(),
            'total_debit' => number_format($debit, 4, '.', ''),
            'total_credit' => number_format($credit, 4, '.', ''),
            'lines' => $entry->lines->map(fn (JournalLine $line) => [
                'id' => $line->id,
                'ledger_account_id' => $line->ledger_account_id,
                'account_code' => $line->account?->code,
                'account_name' => $line->account?->name,
                'debit' => (string) $line->debit,
                'credit' => (string) $line->credit,
                'memo' => $line->memo,
            ])->all(),
        ];
    }

    /**
     * @return list<array{account_id: int, code: string, name: string, type: string, debit: string, credit: string, balance: string}>
     */
    public function trialBalance(): array
    {
        $rows = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->where('journal_entries.status', JournalStatus::Posted->value)
            ->selectRaw('ledger_accounts.id, ledger_accounts.code, ledger_accounts.name, ledger_accounts.type, SUM(journal_lines.debit) as debit, SUM(journal_lines.credit) as credit')
            ->groupBy('ledger_accounts.id', 'ledger_accounts.code', 'ledger_accounts.name', 'ledger_accounts.type')
            ->orderBy('ledger_accounts.code')
            ->get();

        return $rows->map(function ($row) {
            $debit = (float) $row->debit;
            $credit = (float) $row->credit;
            $type = AccountType::tryFrom($row->type) ?? AccountType::Asset;
            $balance = in_array($type, [AccountType::Asset, AccountType::Expense], true)
                ? $debit - $credit
                : $credit - $debit;

            return [
                'account_id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'type' => $row->type,
                'debit' => number_format($debit, 4, '.', ''),
                'credit' => number_format($credit, 4, '.', ''),
                'balance' => number_format($balance, 4, '.', ''),
            ];
        })->all();
    }

    private function alreadyPosted(string $sourceType, int $sourceId): bool
    {
        return JournalEntry::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', JournalStatus::Posted)
            ->exists();
    }

    /**
     * @param  list<array{ledger_account_id: int, debit?: float|int|string, credit?: float|int|string, memo?: string|null}>  $lines
     * @return list<array{ledger_account_id: int, debit: string, credit: string, memo: ?string}>
     */
    private function normalizeLines(array $lines): array
    {
        if ($lines === []) {
            throw ValidationException::withMessages([
                'lines' => 'Add at least two journal lines.',
            ]);
        }

        $normalized = [];

        foreach ($lines as $index => $line) {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);

            if ($debit < 0 || $credit < 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => 'Debit and credit cannot be negative.',
                ]);
            }

            if (($debit > 0 && $credit > 0) || ($debit <= 0 && $credit <= 0)) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => 'Each line needs either a debit or a credit (not both).',
                ]);
            }

            if (! LedgerAccount::query()->whereKey($line['ledger_account_id'])->where('is_active', true)->exists()) {
                throw ValidationException::withMessages([
                    "lines.{$index}.ledger_account_id" => 'Invalid or inactive account.',
                ]);
            }

            $normalized[] = [
                'ledger_account_id' => (int) $line['ledger_account_id'],
                'debit' => number_format($debit, 4, '.', ''),
                'credit' => number_format($credit, 4, '.', ''),
                'memo' => $line['memo'] ?? null,
            ];
        }

        if (count($normalized) < 2) {
            throw ValidationException::withMessages([
                'lines' => 'A journal needs at least two lines.',
            ]);
        }

        return $normalized;
    }

    /**
     * @param  list<array{debit: string, credit: string}>  $lines
     */
    private function assertBalanced(array $lines): void
    {
        $debit = 0.0;
        $credit = 0.0;

        foreach ($lines as $line) {
            $debit += (float) $line['debit'];
            $credit += (float) $line['credit'];
        }

        if (abs($debit - $credit) > 0.00005) {
            throw ValidationException::withMessages([
                'lines' => 'Journal is unbalanced. Debits must equal credits.',
            ]);
        }
    }

    private function nextNumber(): string
    {
        $seq = JournalEntry::query()->lockForUpdate()->count() + 1;

        return 'JE-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatList(JournalEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'number' => $entry->number,
            'entry_date' => $entry->entry_date?->toDateString(),
            'status' => $entry->status->value,
            'status_label' => $entry->status->label(),
            'memo' => $entry->memo,
            'currency' => $entry->currency,
            'lines_count' => $entry->lines_count ?? $entry->lines()->count(),
            'created_at' => $entry->created_at?->toIso8601String(),
        ];
    }
}
