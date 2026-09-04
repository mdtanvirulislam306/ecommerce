<?php

namespace Modules\Commerce\Providers;

use App\Core\Contracts\PriceResolver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Commerce\Services\PriceResolutionService;

class CommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PriceResolver::class, PriceResolutionService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }

    public static function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'verified', 'module:commerce'])
            ->prefix('commerce')
            ->name('commerce.')
            ->group(__DIR__.'/../Routes/web.php');
    }
}
