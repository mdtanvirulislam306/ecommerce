<?php

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Http\Controllers\PurchaseOrderController;
use Modules\Purchase\Http\Controllers\PurchaseOverviewController;
use Modules\Purchase\Http\Controllers\PurchasePaymentController;
use Modules\Purchase\Http\Controllers\PurchaseReceiveController;
use Modules\Purchase\Http\Controllers\PurchaseReportController;
use Modules\Purchase\Http\Controllers\PurchaseReturnController;
use Modules\Purchase\Http\Controllers\SupplierController;
use Modules\Purchase\Http\Controllers\SupplierGroupController;

Route::get('/overview', [PurchaseOverviewController::class, 'index'])->name('overview');

Route::prefix('suppliers')->name('suppliers.')->group(function () {
    Route::get('/all', [SupplierController::class, 'index'])->name('all');
    Route::get('/groups', [SupplierGroupController::class, 'index'])->name('groups');
    Route::post('/groups', [SupplierGroupController::class, 'store'])->name('groups.store');
    Route::put('/groups/{supplierGroup}', [SupplierGroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{supplierGroup}', [SupplierGroupController::class, 'destroy'])->name('groups.destroy');
    Route::post('/', [SupplierController::class, 'store'])->name('store');
    Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update');
    Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
});

Route::get('/receive', [PurchaseReceiveController::class, 'index'])->name('receive');
Route::get('/returns', [PurchaseReturnController::class, 'index'])->name('returns');
Route::post('/returns', [PurchaseReturnController::class, 'store'])->name('returns.store');
Route::get('/payments', [PurchasePaymentController::class, 'index'])->name('payments');
Route::post('/payments', [PurchasePaymentController::class, 'store'])->name('payments.store');
Route::put('/payments/{payment}', [PurchasePaymentController::class, 'update'])->name('payments.update');
Route::delete('/payments/{payment}', [PurchasePaymentController::class, 'destroy'])->name('payments.destroy');
Route::get('/reports', [PurchaseReportController::class, 'index'])->name('reports');

Route::prefix('orders')->name('orders.')->group(function () {
    Route::get('/all', [PurchaseOrderController::class, 'index'])->name('all');
    Route::get('/draft', fn () => redirect()->route('purchase.orders.all', ['status' => 'draft']))->name('draft');
    Route::get('/pending', fn () => redirect()->route('purchase.orders.all', ['status' => 'pending']))->name('pending');
    Route::get('/approved', fn () => redirect()->route('purchase.orders.all', ['status' => 'approved']))->name('approved');
    Route::get('/cancelled', fn () => redirect()->route('purchase.orders.all', ['status' => 'cancelled']))->name('cancelled');

    Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create');
    Route::post('/', [PurchaseOrderController::class, 'store'])->name('store');
    Route::get('/products/{productId}/variants', [PurchaseOrderController::class, 'productVariants'])
        ->name('product-variants')
        ->whereNumber('productId');

    Route::get('/{order}', [PurchaseOrderController::class, 'show'])->name('show');
    Route::post('/{order}/approve', [PurchaseOrderController::class, 'approve'])->name('approve');
    Route::post('/{order}/receive', [PurchaseOrderController::class, 'receive'])->name('receive');
    Route::post('/{order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('cancel');
});
