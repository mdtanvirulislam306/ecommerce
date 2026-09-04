<?php

namespace Modules\Pos\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PosServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }

    public static function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'verified', 'module:pos'])
            ->prefix('pos')
            ->name('pos.')
            ->group(__DIR__.'/../Routes/web.php');
    }
}
