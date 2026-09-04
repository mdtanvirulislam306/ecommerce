<?php

use Illuminate\Support\Facades\Route;
use Modules\Support\Http\Controllers\CannedResponseController;
use Modules\Support\Http\Controllers\MyTicketController;
use Modules\Support\Http\Controllers\SupportCategoryController;
use Modules\Support\Http\Controllers\SupportOverviewController;
use Modules\Support\Http\Controllers\SupportReportController;
use Modules\Support\Http\Controllers\TicketController;
use Modules\Support\Http\Controllers\UnassignedTicketController;

Route::get('/overview', [SupportOverviewController::class, 'index'])->name('overview');
Route::get('/reports', [SupportReportController::class, 'index'])->name('reports');
Route::get('/my-tickets', [MyTicketController::class, 'index'])->name('my-tickets');
Route::get('/unassigned', [UnassignedTicketController::class, 'index'])->name('unassigned');

Route::prefix('tickets')->name('tickets.')->group(function () {
    Route::get('/', [TicketController::class, 'index'])->name('index');
    Route::post('/', [TicketController::class, 'store'])->name('store');
    Route::put('/{supportTicket}', [TicketController::class, 'update'])->name('update');
    Route::delete('/{supportTicket}', [TicketController::class, 'destroy'])->name('destroy');
});

Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [SupportCategoryController::class, 'index'])->name('index');
    Route::post('/', [SupportCategoryController::class, 'store'])->name('store');
    Route::put('/{supportCategory}', [SupportCategoryController::class, 'update'])->name('update');
    Route::delete('/{supportCategory}', [SupportCategoryController::class, 'destroy'])->name('destroy');
});

Route::prefix('canned-responses')->name('canned-responses.')->group(function () {
    Route::get('/', [CannedResponseController::class, 'index'])->name('index');
    Route::post('/', [CannedResponseController::class, 'store'])->name('store');
    Route::put('/{cannedResponse}', [CannedResponseController::class, 'update'])->name('update');
    Route::delete('/{cannedResponse}', [CannedResponseController::class, 'destroy'])->name('destroy');
});
