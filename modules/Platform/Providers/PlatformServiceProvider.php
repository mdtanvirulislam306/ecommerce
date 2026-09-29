<?php

namespace Modules\Platform\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Platform\Services\PlatformSettingService;

class PlatformServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        $this->app->booted(fn () => $this->app->make(PlatformSettingService::class)->applyMailConfig());
    }

    public static function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'verified', 'platform'])
            ->prefix('platform')
            ->name('platform.')
            ->group(__DIR__.'/../Routes/web.php');
    }
}
