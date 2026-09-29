<?php

use App\Http\Middleware\ResolveTenantFromHost;
use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\Storefront\SslCommerzCallbackController;

Route::middleware([ResolveTenantFromHost::class])
    ->prefix('shop/payments/sslcommerz')
    ->name('shop.payments.sslcommerz.')
    ->group(function () {
        Route::match(['get', 'post'], '/success', [SslCommerzCallbackController::class, 'success'])->name('success');
        Route::match(['get', 'post'], '/fail', [SslCommerzCallbackController::class, 'fail'])->name('fail');
        Route::match(['get', 'post'], '/cancel', [SslCommerzCallbackController::class, 'cancel'])->name('cancel');
        Route::post('/ipn', [SslCommerzCallbackController::class, 'ipn'])->name('ipn');
    });
