<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\Storefront\ShopController;

Route::get('/', [ShopController::class, 'index'])->name('index');
Route::get('/products/{slug}', [ShopController::class, 'show'])->name('products.show');
Route::get('/products/{slug}/quick', [ShopController::class, 'quick'])->name('products.quick');
Route::get('/cart', [ShopController::class, 'cart'])->name('cart');
Route::post('/cart', [ShopController::class, 'add'])->name('cart.add');
Route::put('/cart', [ShopController::class, 'update'])->name('cart.update');
Route::delete('/cart', [ShopController::class, 'remove'])->name('cart.remove');
Route::get('/checkout', [ShopController::class, 'checkoutForm'])->name('checkout');
Route::post('/checkout', [ShopController::class, 'checkout'])->name('checkout.store');
Route::get('/thanks/{onlineOrder}', [ShopController::class, 'thanks'])->name('thanks');
