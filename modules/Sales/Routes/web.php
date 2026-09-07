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
    Route::get('/draft', fn () => redirect()->route('sales.quotations.all', ['status' => 'draft']))->name('draft');
    Route::get('/sent', fn () => redirect()->route('sales.quotations.all', ['status' => 'sent']))->name('sent');
    Route::get('/accepted', fn () => redirect()->route('sales.quotations.all', ['status' => 'accepted']))->name('accepted');
    Route::get('/expired', fn () => redirect()->route('sales.quotations.all', ['status' => 'expired']))->name('expired');

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
    Route::get('/export', [SalesOrderController::class, 'export'])->name('export');
    Route::get('/pending', fn () => redirect()->route('sales.orders.all', ['status' => 'pending']))->name('pending');
    Route::get('/confirmed', fn () => redirect()->route('sales.orders.all', ['status' => 'confirmed']))->name('confirmed');
    Route::get('/cancelled', fn () => redirect()->route('sales.orders.all', ['status' => 'cancelled']))->name('cancelled');

    Route::get('/create', [SalesOrderController::class, 'create'])->name('create');
    Route::post('/', [SalesOrderController::class, 'store'])->name('store');

    Route::get('/preview-price', [SalesOrderController::class, 'previewPrice'])->name('preview-price');
    Route::get('/products/{productId}/variants', [SalesOrderController::class, 'productVariants'])
        ->name('product-variants')
        ->whereNumber('productId');

    Route::get('/{order}', [SalesOrderController::class, 'show'])->name('show');
    Route::post('/{order}/confirm', [SalesOrderController::class, 'confirm'])->name('confirm');
    Route::post('/{order}/cancel', [SalesOrderController::class, 'cancel'])->name('cancel');
    Route::post('/{order}/delivery-status', [SalesOrderController::class, 'updateDeliveryStatus'])->name('delivery-status');
    Route::post('/{order}/items/{item}/delivery', [SalesOrderController::class, 'updateLineDelivery'])->name('line-delivery');
    Route::post('/{order}/payments', [SalesOrderController::class, 'recordPayment'])->name('payments');
});

Route::prefix('invoices')->name('invoices.')->group(function () {
    Route::get('/all', [InvoiceController::class, 'index'])->name('all');
    Route::get('/paid', fn () => redirect()->route('sales.invoices.all', ['status' => 'paid']))->name('paid');
    Route::get('/partial', fn () => redirect()->route('sales.invoices.all', ['status' => 'partial']))->name('partial');
    Route::get('/due', fn () => redirect()->route('sales.invoices.all', ['status' => 'due']))->name('due');
    Route::get('/overdue', fn () => redirect()->route('sales.invoices.all', ['status' => 'overdue']))->name('overdue');

    Route::get('/create', [InvoiceController::class, 'create'])->name('create');
    Route::post('/', [InvoiceController::class, 'store'])->name('store');
    Route::get('/{invoice}/print', [InvoiceController::class, 'print'])->name('print');
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
