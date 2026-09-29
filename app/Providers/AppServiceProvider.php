<?php

namespace App\Providers;

use App\Core\Contracts\InventoryOverview;
use App\Core\Contracts\SalesManagerOverview;
use App\Core\Contracts\SalesOverview;
use App\Core\Support\EmptyInventoryOverview;
use App\Core\Support\EmptySalesManagerOverview;
use App\Core\Support\EmptySalesOverview;
use App\Core\Tenant\TenantContext;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(SalesOverview::class, EmptySalesOverview::class);
        $this->app->singleton(SalesManagerOverview::class, EmptySalesManagerOverview::class);
        $this->app->singleton(InventoryOverview::class, EmptyInventoryOverview::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
