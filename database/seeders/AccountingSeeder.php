<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Services\ChartOfAccountsService;

class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        app(ChartOfAccountsService::class)->ensureDefaults();
    }
}
