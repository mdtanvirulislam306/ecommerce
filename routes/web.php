<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalesManagerDashboardController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Modules\Accounting\Providers\AccountingServiceProvider;
use Modules\Billing\Providers\BillingServiceProvider;
use Modules\Catalog\Providers\CatalogServiceProvider;
use Modules\Commerce\Providers\CommerceServiceProvider;
use Modules\Crm\Providers\CrmServiceProvider;
use Modules\Ecommerce\Providers\EcommerceServiceProvider;
use Modules\Files\Providers\FilesServiceProvider;
use Modules\Hrm\Providers\HrmServiceProvider;
use Modules\Inventory\Providers\InventoryServiceProvider;
use Modules\Marketing\Providers\MarketingServiceProvider;
use Modules\Notifications\Providers\NotificationsServiceProvider;
use Modules\Platform\Providers\PlatformServiceProvider;
use Modules\Pos\Providers\PosServiceProvider;
use Modules\Purchase\Providers\PurchaseServiceProvider;
use Modules\Reports\Providers\ReportsServiceProvider;
use Modules\Sales\Providers\SalesServiceProvider;
use Modules\Settings\Providers\SettingsServiceProvider;
use Modules\Support\Providers\SupportServiceProvider;
use Modules\Tasks\Providers\TasksServiceProvider;
use Modules\Workflow\Providers\WorkflowServiceProvider;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::prefix('admin')->group(function () {
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/sales-manager', SalesManagerDashboardController::class)->name('sales-manager.dashboard');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        MarketingServiceProvider::registerRoutes();
        BillingServiceProvider::registerRoutes();
        CrmServiceProvider::registerRoutes();
        CatalogServiceProvider::registerRoutes();
        CommerceServiceProvider::registerRoutes();
        EcommerceServiceProvider::registerRoutes();
        InventoryServiceProvider::registerRoutes();
        PurchaseServiceProvider::registerRoutes();
        SalesServiceProvider::registerRoutes();
        PosServiceProvider::registerRoutes();
        AccountingServiceProvider::registerRoutes();
        HrmServiceProvider::registerRoutes();
        SupportServiceProvider::registerRoutes();
        SettingsServiceProvider::registerRoutes();
        ReportsServiceProvider::registerRoutes();
        WorkflowServiceProvider::registerRoutes();
        TasksServiceProvider::registerRoutes();
        NotificationsServiceProvider::registerRoutes();
        FilesServiceProvider::registerRoutes();
    });
});

require __DIR__.'/auth.php';

PlatformServiceProvider::registerRoutes();

EcommerceServiceProvider::registerStorefrontRoutes();
