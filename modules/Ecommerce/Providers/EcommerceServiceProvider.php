<?php

namespace Modules\Ecommerce\Providers;

use App\Core\Events\OnlineOrderCancelled;
use App\Core\Events\OnlineOrderConfirmed;
use App\Core\Events\OnlineOrderPlaced;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Ecommerce\Listeners\SendOnlineOrderNotifications;

class EcommerceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'ecommerce');
        $this->loadRoutesFrom(__DIR__.'/../Routes/payments.php');

        Event::listen(OnlineOrderPlaced::class, [SendOnlineOrderNotifications::class, 'handlePlaced']);
        Event::listen(OnlineOrderConfirmed::class, [SendOnlineOrderNotifications::class, 'handleConfirmed']);
        Event::listen(OnlineOrderCancelled::class, [SendOnlineOrderNotifications::class, 'handleCancelled']);

        RateLimiter::for('cms-forms', function (Request $request) {
            return Limit::perMinute(5)->by((string) $request->ip());
        });
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
