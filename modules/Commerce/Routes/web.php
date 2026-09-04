<?php

use Illuminate\Support\Facades\Route;
use Modules\Commerce\Http\Controllers\CommerceOverviewController;
use Modules\Commerce\Http\Controllers\CommissionController;
use Modules\Commerce\Http\Controllers\CouponController;
use Modules\Commerce\Http\Controllers\CourierController;
use Modules\Commerce\Http\Controllers\CustomerGroupController;
use Modules\Commerce\Http\Controllers\CustomerWalletController;
use Modules\Commerce\Http\Controllers\LoyaltyPointController;
use Modules\Commerce\Http\Controllers\LoyaltyProgramController;
use Modules\Commerce\Http\Controllers\LoyaltyTransactionController;
use Modules\Commerce\Http\Controllers\PriceCalculatorController;
use Modules\Commerce\Http\Controllers\PriceHistoryController;
use Modules\Commerce\Http\Controllers\PriceListController;
use Modules\Commerce\Http\Controllers\PromotionController;
use Modules\Commerce\Http\Controllers\ReferralController;
use Modules\Commerce\Http\Controllers\ShipmentController;
use Modules\Commerce\Http\Controllers\WalletTransactionController;

Route::get('/overview', [CommerceOverviewController::class, 'index'])->name('overview');

Route::prefix('promotions')->name('promotions.')->group(function () {
    Route::get('/', [PromotionController::class, 'index'])->name('index');
    Route::get('/discount-rules', [PromotionController::class, 'discountRules'])->name('discount-rules');
    Route::get('/buy-x-get-y', [PromotionController::class, 'buyXGetY'])->name('buy-x-get-y');
    Route::get('/free-shipping', [PromotionController::class, 'freeShipping'])->name('free-shipping');
    Route::post('/', [PromotionController::class, 'store'])->name('store');
    Route::put('/{promotion}', [PromotionController::class, 'update'])->name('update');
    Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->name('destroy');
});

Route::prefix('coupons')->name('coupons.')->group(function () {
    Route::get('/', [CouponController::class, 'index'])->name('index');
    Route::post('/', [CouponController::class, 'store'])->name('store');
    Route::put('/{coupon}', [CouponController::class, 'update'])->name('update');
    Route::delete('/{coupon}', [CouponController::class, 'destroy'])->name('destroy');
});

Route::prefix('loyalty')->name('loyalty.')->group(function () {
    Route::get('/program', [LoyaltyProgramController::class, 'index'])->name('program.index');
    Route::post('/program', [LoyaltyProgramController::class, 'store'])->name('program.store');
    Route::put('/program/{loyaltyProgram}', [LoyaltyProgramController::class, 'update'])->name('program.update');

    Route::prefix('points')->name('points.')->group(function () {
        Route::get('/', [LoyaltyPointController::class, 'index'])->name('index');
        Route::post('/', [LoyaltyPointController::class, 'store'])->name('store');
        Route::put('/{loyaltyPoint}', [LoyaltyPointController::class, 'update'])->name('update');
        Route::delete('/{loyaltyPoint}', [LoyaltyPointController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [LoyaltyTransactionController::class, 'index'])->name('index');
        Route::post('/', [LoyaltyTransactionController::class, 'store'])->name('store');
        Route::put('/{loyaltyTransaction}', [LoyaltyTransactionController::class, 'update'])->name('update');
        Route::delete('/{loyaltyTransaction}', [LoyaltyTransactionController::class, 'destroy'])->name('destroy');
    });
});

Route::prefix('wallet')->name('wallet.')->group(function () {
    Route::prefix('wallets')->name('wallets.')->group(function () {
        Route::get('/', [CustomerWalletController::class, 'index'])->name('index');
        Route::post('/', [CustomerWalletController::class, 'store'])->name('store');
        Route::put('/{customerWallet}', [CustomerWalletController::class, 'update'])->name('update');
        Route::delete('/{customerWallet}', [CustomerWalletController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [WalletTransactionController::class, 'index'])->name('index');
        Route::post('/', [WalletTransactionController::class, 'store'])->name('store');
        Route::put('/{walletTransaction}', [WalletTransactionController::class, 'update'])->name('update');
        Route::delete('/{walletTransaction}', [WalletTransactionController::class, 'destroy'])->name('destroy');
    });
});

Route::prefix('referrals')->name('referrals.')->group(function () {
    Route::get('/', [ReferralController::class, 'index'])->name('index');
    Route::post('/', [ReferralController::class, 'store'])->name('store');
    Route::put('/{referral}', [ReferralController::class, 'update'])->name('update');
    Route::delete('/{referral}', [ReferralController::class, 'destroy'])->name('destroy');
});

Route::prefix('commissions')->name('commissions.')->group(function () {
    Route::get('/', [CommissionController::class, 'index'])->name('index');
    Route::post('/', [CommissionController::class, 'store'])->name('store');
    Route::put('/{commission}', [CommissionController::class, 'update'])->name('update');
    Route::delete('/{commission}', [CommissionController::class, 'destroy'])->name('destroy');
});

Route::prefix('shipping')->name('shipping.')->group(function () {
    Route::prefix('couriers')->name('couriers.')->group(function () {
        Route::get('/', [CourierController::class, 'index'])->name('index');
        Route::post('/', [CourierController::class, 'store'])->name('store');
        Route::put('/{courier}', [CourierController::class, 'update'])->name('update');
        Route::delete('/{courier}', [CourierController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('shipments')->name('shipments.')->group(function () {
        Route::get('/', [ShipmentController::class, 'index'])->name('index');
        Route::post('/', [ShipmentController::class, 'store'])->name('store');
        Route::put('/{shipment}', [ShipmentController::class, 'update'])->name('update');
        Route::delete('/{shipment}', [ShipmentController::class, 'destroy'])->name('destroy');
    });

    Route::get('/tracking', [ShipmentController::class, 'tracking'])->name('tracking');
});

Route::prefix('pricing')->name('pricing.')->group(function () {
    Route::get('/quantity', [PriceCalculatorController::class, 'index'])->name('quantity.index');
    Route::get('/history', [PriceHistoryController::class, 'index'])->name('history.index');
    Route::get('/products/{productId}/variants', [PriceListController::class, 'productVariants'])
        ->name('product-variants')
        ->whereNumber('productId');

    Route::get('/product-search', [PriceListController::class, 'productSearch'])->name('product-search');

    Route::prefix('price-lists')->name('price-lists.')->group(function () {
        Route::get('/', [PriceListController::class, 'index'])->name('index');
        Route::post('/', [PriceListController::class, 'store'])->name('store');
        Route::get('/{priceList}', [PriceListController::class, 'show'])->name('show');
        Route::put('/{priceList}', [PriceListController::class, 'update'])->name('update');
        Route::delete('/{priceList}', [PriceListController::class, 'destroy'])->name('destroy');
        Route::post('/{priceList}/items', [PriceListController::class, 'storeItem'])->name('items.store');
        Route::put('/{priceList}/items/{item}', [PriceListController::class, 'updateItem'])->name('items.update');
        Route::delete('/{priceList}/items/{item}', [PriceListController::class, 'destroyItem'])->name('items.destroy');
    });

    Route::prefix('customer-groups')->name('customer-groups.')->group(function () {
        Route::get('/', [CustomerGroupController::class, 'index'])->name('index');
        Route::post('/', [CustomerGroupController::class, 'store'])->name('store');
        Route::post('/quick', [CustomerGroupController::class, 'quickStore'])->name('quick');
        Route::put('/{customerGroup}', [CustomerGroupController::class, 'update'])->name('update');
        Route::delete('/{customerGroup}', [CustomerGroupController::class, 'destroy'])->name('destroy');
    });
});
