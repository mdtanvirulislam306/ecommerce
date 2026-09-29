<?php

use Illuminate\Support\Facades\Route;
use Modules\Platform\Http\Controllers\DashboardController;
use Modules\Platform\Http\Controllers\ImpersonationController;
use Modules\Platform\Http\Controllers\PlanController;
use Modules\Platform\Http\Controllers\SettingsController;
use Modules\Platform\Http\Controllers\TenantController;
use Modules\Platform\Http\Controllers\UserController;

Route::get('/', DashboardController::class)->name('dashboard');

Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
Route::get('/tenants/create', [TenantController::class, 'create'])->name('tenants.create');
Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
Route::get('/tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
Route::post('/tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
Route::post('/tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
Route::post('/tenants/{tenant}/users/{user}/impersonate', [ImpersonationController::class, 'store'])
    ->whereNumber('user')
    ->middleware('throttle:20,1')
    ->name('tenants.impersonate');

Route::get('/users', [UserController::class, 'index'])->name('users.index');

Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
Route::get('/plans/create', [PlanController::class, 'create'])->name('plans.create');
Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
Route::get('/plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');
Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
Route::post('/plans/{plan}/make-default', [PlanController::class, 'makeDefault'])->name('plans.make-default');
Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');

Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
Route::post('/settings/test-email', [SettingsController::class, 'sendTestEmail'])
    ->middleware('throttle:5,1')
    ->name('settings.test-email');
