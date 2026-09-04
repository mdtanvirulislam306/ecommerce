<?php

namespace Modules\Accounting\Providers;

use App\Core\Events\OnlineOrderCancelled;
use App\Core\Events\OnlineOrderConfirmed;
use App\Core\Events\PosSaleCancelled;
use App\Core\Events\PosSaleCompleted;
use App\Core\Events\PurchaseGoodsReceived;
use App\Core\Events\SalesOrderCancelled;
use App\Core\Events\SalesOrderConfirmed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Accounting\Listeners\PostOnlineOrderJournal;
use Modules\Accounting\Listeners\PostPosSaleJournal;
use Modules\Accounting\Listeners\PostPurchaseReceiveJournal;
use Modules\Accounting\Listeners\PostSalesOrderJournal;

class AccountingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        Event::listen(SalesOrderConfirmed::class, [PostSalesOrderJournal::class, 'handleConfirmed']);
        Event::listen(SalesOrderCancelled::class, [PostSalesOrderJournal::class, 'handleCancelled']);
        Event::listen(OnlineOrderConfirmed::class, [PostOnlineOrderJournal::class, 'handleConfirmed']);
        Event::listen(OnlineOrderCancelled::class, [PostOnlineOrderJournal::class, 'handleCancelled']);
        Event::listen(PosSaleCompleted::class, [PostPosSaleJournal::class, 'handleCompleted']);
        Event::listen(PosSaleCancelled::class, [PostPosSaleJournal::class, 'handleCancelled']);
        Event::listen(PurchaseGoodsReceived::class, [PostPurchaseReceiveJournal::class, 'handle']);
    }

    public static function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'verified', 'module:accounting'])
            ->prefix('accounting')
            ->name('accounting.')
            ->group(__DIR__.'/../Routes/web.php');
    }
}
