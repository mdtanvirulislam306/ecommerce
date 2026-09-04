<?php

use Illuminate\Support\Facades\Route;
use Modules\Reports\Http\Controllers\ReportController;

Route::get('/overview', [ReportController::class, 'overview'])->name('overview');
Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
Route::get('/purchase', [ReportController::class, 'purchase'])->name('purchase');
Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
Route::get('/crm', [ReportController::class, 'crm'])->name('crm');
Route::get('/ecommerce', [ReportController::class, 'ecommerce'])->name('ecommerce');
Route::get('/pos', [ReportController::class, 'pos'])->name('pos');
Route::get('/accounting', [ReportController::class, 'accounting'])->name('accounting');
Route::get('/hr', [ReportController::class, 'hr'])->name('hr');
Route::get('/custom', [ReportController::class, 'custom'])->name('custom');
