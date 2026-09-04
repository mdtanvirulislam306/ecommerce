<?php

namespace Modules\Workflow\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class WorkflowServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }

    public static function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'verified', 'module:workflow'])
            ->prefix('workflow')
            ->name('workflow.')
            ->group(__DIR__.'/../Routes/web.php');
    }
}
