<?php

namespace Modules\Hrm\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class HrmServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }

    public static function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'verified', 'module:hrm'])
            ->prefix('hrm')
            ->name('hrm.')
            ->group(__DIR__.'/../Routes/web.php');
    }
}
