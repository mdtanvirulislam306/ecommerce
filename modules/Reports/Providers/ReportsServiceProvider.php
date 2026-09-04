<?php

namespace Modules\Reports\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ReportsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }

    public static function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'verified', 'module:reports'])
            ->prefix('reports')
            ->name('reports.')
            ->group(__DIR__.'/../Routes/web.php');
    }
}
