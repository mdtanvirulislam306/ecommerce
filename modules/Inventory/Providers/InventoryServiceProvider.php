<?php

namespace Modules\Inventory\Providers;

use App\Core\Contracts\StockAvailability;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Services\StockService;

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StockAvailability::class, StockService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }

    public static function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'verified', 'module:inventory'])
            ->prefix('inventory')
            ->name('inventory.')
            ->group(__DIR__.'/../Routes/web.php');
    }
}
