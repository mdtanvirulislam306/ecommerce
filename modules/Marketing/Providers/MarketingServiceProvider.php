<?php

namespace Modules\Marketing\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MarketingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }

    public static function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'verified', 'module:marketing'])
            ->prefix('marketing')
            ->name('marketing.')
            ->group(__DIR__.'/../Routes/web.php');
    }
}
