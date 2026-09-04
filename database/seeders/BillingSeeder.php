<?php

namespace Database\Seeders;

use App\Core\Module\ModuleManager;
use Illuminate\Database\Seeder;
use Modules\Billing\Services\PlanService;

class BillingSeeder extends Seeder
{
    public function run(): void
    {
        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
    }
}
