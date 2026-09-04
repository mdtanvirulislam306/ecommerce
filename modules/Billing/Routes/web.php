<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\Http\Controllers\PlanController;
use Modules\Billing\Http\Controllers\ShopSettingsController;

Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
Route::post('/plans/{plan}/assign', [PlanController::class, 'assign'])->name('plans.assign');

Route::get('/settings', [ShopSettingsController::class, 'edit'])->name('settings.edit');
Route::put('/settings', [ShopSettingsController::class, 'update'])->name('settings.update');
