<?php

namespace Modules\Billing\Providers;

use App\Core\Module\ModuleManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Modules\Billing\Models\Plan;
use Modules\Billing\Services\PlanService;

class BillingServiceProvider extends ServiceProvider
{
    public function boot(PlanService $plans): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        try {
            if (
                Schema::hasTable('plans')
                && ! Plan::query()->where('code', 'free')->exists()
            ) {
                $plans->ensureDefaults(app(ModuleManager::class));
            }
        } catch (\Throwable) {
            // Migrations may not have run yet.
        }
    }

    public static function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'verified', 'module:billing'])
            ->prefix('billing')
            ->name('billing.')
            ->group(__DIR__.'/../Routes/web.php');
    }
}
