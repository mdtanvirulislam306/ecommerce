<?php

namespace App\Providers;

use App\Core\Contracts\InventoryOverview;
use App\Core\Contracts\SalesOverview;
use App\Core\Contracts\SmsSender;
use App\Core\Permission\PermissionRegistry;
use App\Core\Support\BulkSmsBdSender;
use App\Core\Support\EmptyInventoryOverview;
use App\Core\Support\EmptySalesOverview;
use App\Core\Support\LogSmsSender;
use App\Core\Tenant\TenantContext;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
        $this->app->singleton(PermissionRegistry::class);
        $this->app->singleton(SalesOverview::class, EmptySalesOverview::class);
        $this->app->singleton(InventoryOverview::class, EmptyInventoryOverview::class);
        $this->app->bind(SmsSender::class, fn (): SmsSender => match (config('services.sms.driver')) {
            'bulksmsbd' => new BulkSmsBdSender(
                apiKey: (string) config('services.sms.bulksmsbd.api_key'),
                senderId: (string) config('services.sms.bulksmsbd.sender_id'),
                endpoint: (string) config('services.sms.bulksmsbd.endpoint'),
            ),
            default => new LogSmsSender,
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->hasFullShopAccess() || $user->hasPermission($ability)) {
                return true;
            }

            return null;
        });
    }
}
