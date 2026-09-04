<?php

use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\CreditNoteController;
use Modules\Sales\Http\Controllers\InvoiceController;
use Modules\Sales\Http\Controllers\PaymentController;
use Modules\Sales\Http\Controllers\QuotationController;
use Modules\Sales\Http\Controllers\SalesOrderController;
use Modules\Sales\Http\Controllers\SalesOverviewController;
use Modules\Sales\Http\Controllers\SalesReportController;
use Modules\Sales\Http\Controllers\SalesReturnController;

Route::get('/overview', [SalesOverviewController::class, 'index'])->name('overview');

Route::prefix('quotations')->name('quotations.')->group(function () {
    Route::get('/all', [QuotationController::class, 'index'])->name('all');
    Route::get('/draft', [QuotationController::class, 'index'])->name('draft');
    Route::get('/sent', [QuotationController::class, 'index'])->name('sent');
    Route::get('/accepted', [QuotationController::class, 'index'])->name('accepted');
    Route::get('/expired', [QuotationController::class, 'index'])->name('expired');

    Route::get('/create', [QuotationController::class, 'create'])->name('create');
    Route::post('/', [QuotationController::class, 'store'])->name('store');

    Route::get('/{quotation}', [QuotationController::class, 'show'])->name('show');
    Route::get('/{quotation}/edit', [QuotationController::class, 'edit'])->name('edit');
    Route::put('/{quotation}', [QuotationController::class, 'update'])->name('update');
    Route::post('/{quotation}/mark-sent', [QuotationController::class, 'markSent'])->name('mark-sent');
    Route::post('/{quotation}/mark-accepted', [QuotationController::class, 'markAccepted'])->name('mark-accepted');
    Route::post('/{quotation}/mark-expired', [QuotationController::class, 'markExpired'])->name('mark-expired');
    Route::post('/{quotation}/convert', [QuotationController::class, 'convert'])->name('convert');
});

Route::prefix('orders')->name('orders.')->group(function () {
    Route::get('/all', [SalesOrderController::class, 'index'])->name('all');
    Route::get('/pending', [SalesOrderController::class, 'index'])->name('pending');
    Route::get('/confirmed', [SalesOrderController::class, 'index'])->name('confirmed');
    Route::get('/cancelled', [SalesOrderController::class, 'index'])->name('cancelled');

    Route::get('/create', [SalesOrderController::class, 'create'])->name('create');
    Route::post('/', [SalesOrderController::class, 'store'])->name('store');

    Route::get('/preview-price', [SalesOrderController::class, 'previewPrice'])->name('preview-price');
    Route::get('/products/{productId}/variants', [SalesOrderController::class, 'productVariants'])
        ->name('product-variants')
        ->whereNumber('productId');

    Route::get('/{order}', [SalesOrderController::class, 'show'])->name('show');
    Route::post('/{order}/confirm', [SalesOrderController::class, 'confirm'])->name('confirm');
    Route::post('/{order}/cancel', [SalesOrderController::class, 'cancel'])->name('cancel');
});

Route::prefix('invoices')->name('invoices.')->group(function () {
    Route::get('/all', [InvoiceController::class, 'index'])->name('all');
    Route::get('/paid', [InvoiceController::class, 'index'])->name('paid');
    Route::get('/partial', [InvoiceController::class, 'index'])->name('partial');
    Route::get('/due', [InvoiceController::class, 'index'])->name('due');
    Route::get('/overdue', [InvoiceController::class, 'index'])->name('overdue');

    Route::get('/create', [InvoiceController::class, 'create'])->name('create');
    Route::post('/', [InvoiceController::class, 'store'])->name('store');
    Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
});

Route::prefix('payments')->name('payments.')->group(function () {
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    Route::post('/', [PaymentController::class, 'store'])->name('store');
});

Route::prefix('returns')->name('returns.')->group(function () {
    Route::get('/', [SalesReturnController::class, 'index'])->name('index');
    Route::get('/create', [SalesReturnController::class, 'create'])->name('create');
    Route::post('/', [SalesReturnController::class, 'store'])->name('store');
    Route::get('/{salesReturn}', [SalesReturnController::class, 'show'])->name('show');
    Route::post('/{salesReturn}/confirm', [SalesReturnController::class, 'confirm'])->name('confirm');
});

Route::prefix('credit-notes')->name('credit-notes.')->group(function () {
    Route::get('/', [CreditNoteController::class, 'index'])->name('index');
    Route::post('/', [CreditNoteController::class, 'store'])->name('store');
});

Route::get('/reports', [SalesReportController::class, 'index'])->name('reports.index');
