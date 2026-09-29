<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\CheckoutSettingsController;
use Modules\Ecommerce\Http\Controllers\CmsMenuController;
use Modules\Ecommerce\Http\Controllers\CmsPageBuilderController;
use Modules\Ecommerce\Http\Controllers\CmsPageController;
use Modules\Ecommerce\Http\Controllers\EcommerceCouponController;
use Modules\Ecommerce\Http\Controllers\EcommerceOverviewController;
use Modules\Ecommerce\Http\Controllers\EcommercePromotionController;
use Modules\Ecommerce\Http\Controllers\EcommerceReportController;
use Modules\Ecommerce\Http\Controllers\OnlineOrderController;
use Modules\Ecommerce\Http\Controllers\OnlineProductController;
use Modules\Ecommerce\Http\Controllers\OrderNotificationSettingsController;
use Modules\Ecommerce\Http\Controllers\ProductReviewController;
use Modules\Ecommerce\Http\Controllers\ShippingSettingsController;
use Modules\Ecommerce\Http\Controllers\StoreDashboardController;
use Modules\Ecommerce\Http\Controllers\StoreDomainController;
use Modules\Ecommerce\Http\Controllers\StoreSeoController;
use Modules\Ecommerce\Http\Controllers\StoreSettingsController;
use Modules\Ecommerce\Http\Controllers\ThemeCustomizeController;
use Modules\Ecommerce\Http\Controllers\ThemeInstalledController;
use Modules\Ecommerce\Http\Controllers\ThemeLibraryController;
use Modules\Ecommerce\Http\Controllers\ThemePublishController;

Route::get('/overview', [EcommerceOverviewController::class, 'index'])->name('overview');

Route::prefix('online-products')->name('online-products.')->group(function () {
    Route::get('/', [OnlineProductController::class, 'index'])->name('index');
    Route::post('/bulk-presentation', [OnlineProductController::class, 'bulkPresentation'])->name('bulk-presentation');
    Route::post('/publish', [OnlineProductController::class, 'bulkPublish'])->name('bulk-publish');
    Route::post('/unpublish', [OnlineProductController::class, 'bulkUnpublish'])->name('bulk-unpublish');
    Route::put('/{productId}/presentation', [OnlineProductController::class, 'updatePresentation'])
        ->name('update-presentation')
        ->whereNumber('productId');
    Route::post('/{productId}/publish', [OnlineProductController::class, 'publish'])
        ->name('publish')
        ->whereNumber('productId');
    Route::post('/{productId}/unpublish', [OnlineProductController::class, 'unpublish'])
        ->name('unpublish')
        ->whereNumber('productId');
});

Route::prefix('online-orders')->name('online-orders.')->group(function () {
    Route::get('/', [OnlineOrderController::class, 'index'])->name('index');
    Route::get('/{onlineOrder}', [OnlineOrderController::class, 'show'])->name('show');
    Route::post('/{onlineOrder}/confirm', [OnlineOrderController::class, 'confirm'])->name('confirm');
    Route::post('/{onlineOrder}/cancel', [OnlineOrderController::class, 'cancel'])->name('cancel');
});

Route::prefix('reviews')->name('reviews.')->group(function () {
    Route::get('/', [ProductReviewController::class, 'index'])->name('index');
    Route::post('/bulk-approve', [ProductReviewController::class, 'bulkApprove'])->name('bulk-approve');
    Route::post('/bulk-reject', [ProductReviewController::class, 'bulkReject'])->name('bulk-reject');
    Route::post('/{review}/approve', [ProductReviewController::class, 'approve'])->name('approve');
    Route::post('/{review}/reject', [ProductReviewController::class, 'reject'])->name('reject');
    Route::delete('/{review}', [ProductReviewController::class, 'destroy'])->name('destroy');
});

Route::prefix('store')->name('store.')->group(function () {
    Route::get('/dashboard', [StoreDashboardController::class, 'index'])->name('dashboard');
    Route::get('/settings', [StoreSettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [StoreSettingsController::class, 'update'])->name('settings.update');
    Route::get('/seo', [StoreSeoController::class, 'index'])->name('seo.index');
    Route::put('/seo', [StoreSeoController::class, 'update'])->name('seo.update');

    Route::prefix('domains')->name('domains.')->group(function () {
        Route::get('/', [StoreDomainController::class, 'index'])->name('index');
        Route::post('/', [StoreDomainController::class, 'store'])->name('store');
        Route::put('/{storeDomain}', [StoreDomainController::class, 'update'])->name('update');
        Route::delete('/{storeDomain}', [StoreDomainController::class, 'destroy'])->name('destroy');
    });
});

Route::prefix('theme')->name('theme.')->group(function () {
    Route::get('/library', [ThemeLibraryController::class, 'index'])->name('library');
    Route::post('/library/install', [ThemeLibraryController::class, 'install'])->name('library.install');
    Route::get('/installed', [ThemeInstalledController::class, 'index'])->name('installed');
    Route::post('/installed/{themeSetting}/activate', [ThemeInstalledController::class, 'activate'])->name('installed.activate');
    Route::get('/customize', [ThemeCustomizeController::class, 'index'])->name('customize.index');
    Route::put('/customize', [ThemeCustomizeController::class, 'update'])->name('customize.update');
    Route::get('/publish', [ThemePublishController::class, 'index'])->name('publish.index');
    Route::post('/publish', [ThemePublishController::class, 'publish'])->name('publish');
});

Route::prefix('pages')->name('pages.')->group(function () {
    Route::prefix('all')->name('all.')->group(function () {
        Route::get('/', [CmsPageController::class, 'index'])->name('index');
        Route::post('/', [CmsPageController::class, 'store'])->name('store');
        Route::put('/{cmsPage}', [CmsPageController::class, 'update'])->name('update');
        Route::delete('/{cmsPage}', [CmsPageController::class, 'destroy'])->name('destroy');
    });

    Route::get('/builder', [CmsPageBuilderController::class, 'show'])->name('builder');
    Route::post('/builder', [CmsPageBuilderController::class, 'store'])->name('builder.store');
    Route::get('/builder/{cmsPage}', [CmsPageBuilderController::class, 'show'])->name('builder.edit');
    Route::put('/builder/{cmsPage}', [CmsPageBuilderController::class, 'update'])->name('builder.update');

    Route::prefix('menus')->name('menus.')->group(function () {
        Route::get('/', [CmsMenuController::class, 'index'])->name('index');
        Route::post('/', [CmsMenuController::class, 'store'])->name('store');
        Route::put('/{cmsMenu}', [CmsMenuController::class, 'update'])->name('update');
        Route::delete('/{cmsMenu}', [CmsMenuController::class, 'destroy'])->name('destroy');
    });
});

Route::prefix('coupons')->name('coupons.')->group(function () {
    Route::get('/', [EcommerceCouponController::class, 'index'])->name('index');
    Route::post('/', [EcommerceCouponController::class, 'store'])->name('store');
    Route::put('/{ecommerceCoupon}', [EcommerceCouponController::class, 'update'])->name('update');
    Route::delete('/{ecommerceCoupon}', [EcommerceCouponController::class, 'destroy'])->name('destroy');
});

Route::prefix('promotions')->name('promotions.')->group(function () {
    Route::get('/', [EcommercePromotionController::class, 'index'])->name('index');
    Route::post('/', [EcommercePromotionController::class, 'store'])->name('store');
    Route::put('/{ecommercePromotion}', [EcommercePromotionController::class, 'update'])->name('update');
    Route::delete('/{ecommercePromotion}', [EcommercePromotionController::class, 'destroy'])->name('destroy');
});

Route::prefix('shipping')->name('shipping.')->group(function () {
    Route::get('/', [ShippingSettingsController::class, 'index'])->name('index');
    Route::put('/', [ShippingSettingsController::class, 'update'])->name('update');
});

Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [CheckoutSettingsController::class, 'index'])->name('index');
    Route::put('/', [CheckoutSettingsController::class, 'update'])->name('update');
});

Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [OrderNotificationSettingsController::class, 'index'])->name('index');
    Route::put('/', [OrderNotificationSettingsController::class, 'update'])->name('update');
    Route::get('/preview/{template}', [OrderNotificationSettingsController::class, 'preview'])
        ->whereIn('template', OrderNotificationSettingsController::PREVIEWS)
        ->name('preview');
});

Route::get('/reports', [EcommerceReportController::class, 'index'])->name('reports.index');
