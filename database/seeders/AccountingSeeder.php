<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Services\ChartOfAccountsService;
use Modules\Accounting\Services\JournalService;

class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = app(ChartOfAccountsService::class);
        $accounts->ensureDefaults();

        if (DB::table('journal_entries')->where('memo', 'Opening cash and equity (seeder)')->exists()) {
            return;
        }

        $cash = $accounts->findByCode('1000');
        $equity = $accounts->findByCode('3000');
        $adminId = User::query()->where('email', 'admin@admin.com')->value('id');

        if ($cash === null || $equity === null) {
            return;
        }

        app(JournalService::class)->post([
            'entry_date' => now()->toDateString(),
            'memo' => 'Opening cash and equity (seeder)',
            'currency' => 'BDT',
            'source_type' => 'seeder',
            'lines' => [
                ['ledger_account_id' => $cash->id, 'debit' => 100000, 'credit' => 0, 'memo' => 'Opening cash'],
                ['ledger_account_id' => $equity->id, 'debit' => 0, 'credit' => 100000, 'memo' => 'Owner equity'],
            ],
        ], $adminId);
    }
}
