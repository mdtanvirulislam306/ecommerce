<?php

namespace Modules\Ecommerce\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class EcommerceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }

    public static function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'verified', 'module:ecommerce'])
            ->prefix('ecommerce')
            ->name('ecommerce.')
            ->group(__DIR__.'/../Routes/web.php');
    }

    public static function registerStorefrontRoutes(): void
    {
        Route::middleware(['web', 'module:ecommerce'])
            ->prefix('shop')
            ->name('shop.')
            ->group(__DIR__.'/../Routes/storefront.php');
    }
}
