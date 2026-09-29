<?php

namespace Modules\Sales\Providers;

use App\Core\Contracts\SalesManagerOverview;
use App\Core\Contracts\SalesOverview;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Sales\Services\SalesManagerOverviewReader;
use Modules\Sales\Services\SalesOverviewReader;

class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->forgetInstance(SalesOverview::class);
        $this->app->singleton(SalesOverview::class, SalesOverviewReader::class);
        $this->app->forgetInstance(SalesManagerOverview::class);
        $this->app->singleton(SalesManagerOverview::class, SalesManagerOverviewReader::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'sales');
    }

    public static function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'verified', 'module:sales'])
            ->prefix('sales')
            ->name('sales.')
            ->group(__DIR__.'/../Routes/web.php');
    }
}
