<?php

use Illuminate\Support\Facades\Route;
use Modules\Pos\Http\Controllers\PosOrderController;
use Modules\Pos\Http\Controllers\PosRegisterController;
use Modules\Pos\Http\Controllers\PosReportController;
use Modules\Pos\Http\Controllers\PosReturnController;
use Modules\Pos\Http\Controllers\PosSessionController;
use Modules\Pos\Http\Controllers\PosTerminalController;

Route::get('/terminal', [PosTerminalController::class, 'index'])->name('terminal');
Route::get('/terminal/search', [PosTerminalController::class, 'search'])->name('terminal.search');
Route::post('/terminal/complete', [PosTerminalController::class, 'complete'])->name('terminal.complete');

Route::prefix('registers')->name('registers.')->group(function () {
    Route::get('/', [PosRegisterController::class, 'index'])->name('index');
    Route::post('/', [PosRegisterController::class, 'store'])->name('store');
    Route::put('/{posRegister}', [PosRegisterController::class, 'update'])->name('update');
    Route::post('/{posRegister}/open-session', [PosRegisterController::class, 'openSession'])->name('open-session');
});

Route::post('/sessions/{posSession}/close', [PosRegisterController::class, 'closeSession'])->name('sessions.close');
Route::get('/open-sessions', [PosSessionController::class, 'open'])->name('open-sessions');
Route::get('/session-history', [PosSessionController::class, 'history'])->name('session-history');
Route::get('/cash-management', [PosSessionController::class, 'cash'])->name('cash-management');
Route::post('/cash-management', [PosSessionController::class, 'storeCash'])->name('cash-management.store');
Route::get('/returns', [PosReturnController::class, 'index'])->name('returns');
Route::post('/returns', [PosReturnController::class, 'store'])->name('returns.store');
Route::get('/reports', [PosReportController::class, 'index'])->name('reports');

Route::prefix('orders')->name('orders.')->group(function () {
    Route::get('/', [PosOrderController::class, 'index'])->name('index');
    Route::get('/{posOrder}', [PosOrderController::class, 'show'])->name('show');
    Route::post('/{posOrder}/cancel', [PosOrderController::class, 'cancel'])->name('cancel');
});
