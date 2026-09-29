<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\Storefront\CmsPageController;
use Modules\Ecommerce\Http\Controllers\Storefront\CustomerAccountController;
use Modules\Ecommerce\Http\Controllers\Storefront\CustomerAuthController;
use Modules\Ecommerce\Http\Controllers\Storefront\CustomerPasswordResetController;
use Modules\Ecommerce\Http\Controllers\Storefront\OrderPaymentController;
use Modules\Ecommerce\Http\Controllers\Storefront\ShopController;

Route::get('/', [ShopController::class, 'index'])->name('index');
Route::get('/pages/{slug}', [CmsPageController::class, 'show'])->name('pages.show');
Route::get('/pages/{slug}/preview', [CmsPageController::class, 'preview'])
    ->middleware(['auth', 'verified'])
    ->name('pages.preview');
Route::post('/pages/{slug}/forms', [CmsPageController::class, 'submitForm'])
    ->middleware('throttle:cms-forms')
    ->name('pages.forms.store');
Route::get('/products/{slug}', [ShopController::class, 'show'])->name('products.show');
Route::get('/products/{slug}/quick', [ShopController::class, 'quick'])->name('products.quick');
Route::get('/cart', [ShopController::class, 'cart'])->name('cart');
Route::post('/cart', [ShopController::class, 'add'])->name('cart.add');
Route::put('/cart', [ShopController::class, 'update'])->name('cart.update');
Route::delete('/cart', [ShopController::class, 'remove'])->name('cart.remove');
Route::post('/cart/coupon', [ShopController::class, 'applyCoupon'])
    ->middleware('throttle:10,1')
    ->name('cart.coupon.apply');
Route::delete('/cart/coupon', [ShopController::class, 'removeCoupon'])->name('cart.coupon.remove');
Route::get('/checkout', [ShopController::class, 'checkoutForm'])->name('checkout');
Route::post('/checkout', [ShopController::class, 'checkout'])->name('checkout.store');
Route::get('/orders/{token}', [ShopController::class, 'order'])
    ->where('token', '[A-Za-z0-9]{40}')
    ->name('orders.show');
Route::post('/orders/{token}/pay', [OrderPaymentController::class, 'pay'])
    ->where('token', '[A-Za-z0-9]{40}')
    ->middleware('throttle:10,1')
    ->name('orders.pay');
Route::post('/orders/{token}/cash-on-delivery', [OrderPaymentController::class, 'payOnDelivery'])
    ->where('token', '[A-Za-z0-9]{40}')
    ->name('orders.cash-on-delivery');

Route::prefix('account')->name('account.')->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('/login', [CustomerAuthController::class, 'createLogin'])->name('login');
        Route::post('/login', [CustomerAuthController::class, 'storeLogin'])->middleware('throttle:10,1')->name('login.store');
        Route::get('/register', [CustomerAuthController::class, 'createRegister'])->name('register');
        Route::post('/register', [CustomerAuthController::class, 'storeRegister'])->middleware('throttle:10,1')->name('register.store');
        Route::get('/forgot-password', [CustomerPasswordResetController::class, 'create'])->name('password.request');
        Route::post('/forgot-password', [CustomerPasswordResetController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
        Route::get('/reset-password/{token}', [CustomerPasswordResetController::class, 'edit'])->name('password.reset');
        Route::post('/reset-password', [CustomerPasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('password.update');
    });

    Route::middleware('auth:customer')->group(function () {
        Route::get('/', [CustomerAccountController::class, 'index'])->name('index');
        Route::put('/profile', [CustomerAccountController::class, 'updateProfile'])->name('profile.update');
        Route::put('/password', [CustomerAccountController::class, 'updatePassword'])->name('password.change');
        Route::post('/logout', [CustomerAuthController::class, 'destroy'])->name('logout');
    });
});
