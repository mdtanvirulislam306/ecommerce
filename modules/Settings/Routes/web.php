<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\ApiSettingsController;
use Modules\Settings\Http\Controllers\AreasController;
use Modules\Settings\Http\Controllers\AuditLogController;
use Modules\Settings\Http\Controllers\BranchesController;
use Modules\Settings\Http\Controllers\BusinessProfileController;
use Modules\Settings\Http\Controllers\CurrencyController;
use Modules\Settings\Http\Controllers\DocumentTemplatesController;
use Modules\Settings\Http\Controllers\GeneralSettingsController;
use Modules\Settings\Http\Controllers\IntegrationsController;
use Modules\Settings\Http\Controllers\LocalizationController;
use Modules\Settings\Http\Controllers\ModulesController;
use Modules\Settings\Http\Controllers\NotificationsController;
use Modules\Settings\Http\Controllers\NumberingSeriesController;
use Modules\Settings\Http\Controllers\PaymentMethodController;
use Modules\Settings\Http\Controllers\RegionsController;
use Modules\Settings\Http\Controllers\RoleController;
use Modules\Settings\Http\Controllers\ShippingMethodController;
use Modules\Settings\Http\Controllers\StaffController;
use Modules\Settings\Http\Controllers\SubscriptionController;
use Modules\Settings\Http\Controllers\SystemController;
use Modules\Settings\Http\Controllers\TaxRateController;
use Modules\Settings\Http\Controllers\UserListController;
use Modules\Settings\Http\Controllers\UserRoleController;
use Modules\Settings\Http\Controllers\WarehousesController;

Route::get('/general', [GeneralSettingsController::class, 'edit'])->name('general.edit');
Route::put('/general', [GeneralSettingsController::class, 'update'])->name('general.update');

Route::get('/business/company', [BusinessProfileController::class, 'edit'])->name('business.company.edit');
Route::put('/business/company', [BusinessProfileController::class, 'update'])->name('business.company.update');
Route::get('/business/branches', [BranchesController::class, 'index'])->name('business.branches');
Route::get('/business/regions', [RegionsController::class, 'index'])->name('business.regions');
Route::get('/business/areas', [AreasController::class, 'index'])->name('business.areas');

Route::get('/warehouses', [WarehousesController::class, 'index'])->name('warehouses');
Route::prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserListController::class, 'index'])->name('index');
    Route::post('/', [StaffController::class, 'store'])->middleware('throttle:20,1')->name('store');
    Route::put('/{user}/roles', [UserRoleController::class, 'update'])->whereNumber('user')->name('assign-roles');
    Route::post('/{user}/resend-invitation', [StaffController::class, 'resendInvitation'])
        ->whereNumber('user')
        ->middleware('throttle:10,1')
        ->name('resend-invitation');
    Route::post('/{user}/deactivate', [StaffController::class, 'deactivate'])->whereNumber('user')->name('deactivate');
    Route::post('/{user}/reactivate', [StaffController::class, 'reactivate'])->whereNumber('user')->name('reactivate');
    Route::delete('/{user}', [StaffController::class, 'destroy'])->whereNumber('user')->name('destroy');
});
Route::get('/modules', [ModulesController::class, 'index'])->name('modules');
Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription');
Route::get('/document-templates', [DocumentTemplatesController::class, 'index'])->name('document-templates');
Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications');
Route::get('/system', [SystemController::class, 'index'])->name('system');

Route::get('/localization', [LocalizationController::class, 'edit'])->name('localization.edit');
Route::put('/localization', [LocalizationController::class, 'update'])->name('localization.update');
Route::get('/integrations', [IntegrationsController::class, 'edit'])->name('integrations.edit');
Route::put('/integrations', [IntegrationsController::class, 'update'])->name('integrations.update');
Route::get('/api', [ApiSettingsController::class, 'edit'])->name('api.edit');
Route::put('/api', [ApiSettingsController::class, 'update'])->name('api.update');

Route::prefix('roles')->name('roles.')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('index');
    Route::post('/', [RoleController::class, 'store'])->name('store');
    Route::put('/{role}', [RoleController::class, 'update'])->name('update');
    Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
});

Route::prefix('numbering')->name('numbering.')->group(function () {
    Route::get('/', [NumberingSeriesController::class, 'index'])->name('index');
    Route::post('/', [NumberingSeriesController::class, 'store'])->name('store');
    Route::put('/{numberingSeries}', [NumberingSeriesController::class, 'update'])->name('update');
    Route::delete('/{numberingSeries}', [NumberingSeriesController::class, 'destroy'])->name('destroy');
});

Route::prefix('tax')->name('tax.')->group(function () {
    Route::get('/', [TaxRateController::class, 'index'])->name('index');
    Route::post('/', [TaxRateController::class, 'store'])->name('store');
    Route::put('/{taxRate}', [TaxRateController::class, 'update'])->name('update');
    Route::delete('/{taxRate}', [TaxRateController::class, 'destroy'])->name('destroy');
});

Route::prefix('currency')->name('currency.')->group(function () {
    Route::get('/', [CurrencyController::class, 'index'])->name('index');
    Route::post('/', [CurrencyController::class, 'store'])->name('store');
    Route::put('/{currency}', [CurrencyController::class, 'update'])->name('update');
    Route::delete('/{currency}', [CurrencyController::class, 'destroy'])->name('destroy');
});

Route::prefix('payment-methods')->name('payment-methods.')->group(function () {
    Route::get('/', [PaymentMethodController::class, 'index'])->name('index');
    Route::post('/', [PaymentMethodController::class, 'store'])->name('store');
    Route::put('/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('update');
    Route::delete('/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('destroy');
});

Route::prefix('shipping-methods')->name('shipping-methods.')->group(function () {
    Route::get('/', [ShippingMethodController::class, 'index'])->name('index');
    Route::post('/', [ShippingMethodController::class, 'store'])->name('store');
    Route::put('/{shippingMethod}', [ShippingMethodController::class, 'update'])->name('update');
    Route::delete('/{shippingMethod}', [ShippingMethodController::class, 'destroy'])->name('destroy');
});

Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
    Route::get('/', [AuditLogController::class, 'index'])->name('index');
    Route::post('/', [AuditLogController::class, 'store'])->name('store');
    Route::put('/{auditLog}', [AuditLogController::class, 'update'])->name('update');
    Route::delete('/{auditLog}', [AuditLogController::class, 'destroy'])->name('destroy');
});
