<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\AttributeController;
use Modules\Catalog\Http\Controllers\BrandController;
use Modules\Catalog\Http\Controllers\CatalogOverviewController;
use Modules\Catalog\Http\Controllers\CatalogSettingsController;
use Modules\Catalog\Http\Controllers\CategoryController;
use Modules\Catalog\Http\Controllers\CollectionController;
use Modules\Catalog\Http\Controllers\ProductApprovalController;
use Modules\Catalog\Http\Controllers\ProductController;
use Modules\Catalog\Http\Controllers\ProductFamilyController;
use Modules\Catalog\Http\Controllers\ProductImportExportController;
use Modules\Catalog\Http\Controllers\UnitController;
use Modules\Catalog\Http\Controllers\VariantController;

Route::get('/overview', [CatalogOverviewController::class, 'index'])->name('overview');

Route::prefix('import-export')->name('import-export.')->group(function () {
    Route::get('/', [ProductImportExportController::class, 'index'])->name('index');
    Route::get('/export', [ProductImportExportController::class, 'export'])->name('export');
    Route::get('/template', [ProductImportExportController::class, 'template'])->name('template');
    Route::post('/import', [ProductImportExportController::class, 'import'])->name('import');
});

Route::get('/settings', [CatalogSettingsController::class, 'index'])->name('settings.index');
Route::put('/settings', [CatalogSettingsController::class, 'update'])->name('settings.update');

Route::get('/all-products', [ProductController::class, 'index'])->name('index');
Route::get('/draft', fn () => redirect()->route('products.index', ['status' => 'draft']))->name('draft');
Route::get('/pending-approval', fn () => redirect()->route('products.index', ['status' => 'pending_review']))->name('pending');
Route::get('/active', fn () => redirect()->route('products.index', ['status' => 'active']))->name('active');
Route::get('/archived', fn () => redirect()->route('products.index', ['status' => 'archived']))->name('archived');
Route::get('/create', [ProductController::class, 'create'])->name('create');
Route::post('/', [ProductController::class, 'store'])->name('store');

Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::post('/', [CategoryController::class, 'store'])->name('store');
    Route::post('/quick', [CategoryController::class, 'quickStore'])->name('quick');
    Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
    Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
});

Route::prefix('brands')->name('brands.')->group(function () {
    Route::get('/', [BrandController::class, 'index'])->name('index');
    Route::post('/', [BrandController::class, 'store'])->name('store');
    Route::post('/quick', [BrandController::class, 'quickStore'])->name('quick');
    Route::put('/{brand}', [BrandController::class, 'update'])->name('update');
    Route::delete('/{brand}', [BrandController::class, 'destroy'])->name('destroy');
});

Route::prefix('collections')->name('collections.')->group(function () {
    Route::get('/', [CollectionController::class, 'index'])->name('index');
    Route::post('/', [CollectionController::class, 'store'])->name('store');
    Route::post('/quick', [CollectionController::class, 'quickStore'])->name('quick');
    Route::put('/{collection}', [CollectionController::class, 'update'])->name('update');
    Route::delete('/{collection}', [CollectionController::class, 'destroy'])->name('destroy');
});

Route::prefix('units')->name('units.')->group(function () {
    Route::get('/conversions', [UnitController::class, 'conversions'])->name('conversions.index');
    Route::post('/conversions', [UnitController::class, 'storeConversion'])->name('conversions.store');
    Route::delete('/conversions/{conversion}', [UnitController::class, 'destroyConversion'])->name('conversions.destroy');

    Route::get('/', [UnitController::class, 'index'])->name('index');
    Route::post('/', [UnitController::class, 'store'])->name('store');
    Route::put('/{unit}', [UnitController::class, 'update'])->name('update');
    Route::delete('/{unit}', [UnitController::class, 'destroy'])->name('destroy');
});

Route::prefix('attributes')->name('attributes.')->group(function () {
    Route::get('/', [AttributeController::class, 'index'])->name('index');
    Route::post('/', [AttributeController::class, 'store'])->name('store');
    Route::put('/{attribute}', [AttributeController::class, 'update'])->name('update');
    Route::delete('/{attribute}', [AttributeController::class, 'destroy'])->name('destroy');
});

Route::prefix('families')->name('families.')->group(function () {
    Route::get('/', [ProductFamilyController::class, 'index'])->name('index');
    Route::post('/', [ProductFamilyController::class, 'store'])->name('store');
    Route::put('/{family}', [ProductFamilyController::class, 'update'])->name('update');
    Route::delete('/{family}', [ProductFamilyController::class, 'destroy'])->name('destroy');
});

Route::get('/variants', [VariantController::class, 'index'])->name('variants.index');

Route::get('/approval', [ProductApprovalController::class, 'index'])->name('approval.index');
Route::post('/approval/submit', [ProductApprovalController::class, 'bulkSubmit'])->name('approval.bulk-submit');
Route::post('/approval/approve', [ProductApprovalController::class, 'bulkApprove'])->name('approval.bulk-approve');
Route::post('/approval/reject', [ProductApprovalController::class, 'bulkReject'])->name('approval.bulk-reject');
Route::post('/approval/activate', [ProductApprovalController::class, 'bulkActivate'])->name('approval.bulk-activate');

Route::post('/{product}/submit-review', [ProductApprovalController::class, 'submitReview'])->name('submit-review');
Route::post('/{product}/approve', [ProductApprovalController::class, 'approve'])->name('approve');
Route::post('/{product}/reject', [ProductApprovalController::class, 'reject'])->name('reject');
Route::post('/{product}/activate', [ProductApprovalController::class, 'activate'])->name('activate');
Route::post('/{product}/archive', [ProductApprovalController::class, 'archive'])->name('archive');

Route::get('/{product}', [ProductController::class, 'show'])->name('show');
Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
Route::match(['put', 'patch'], '/{product}', [ProductController::class, 'update'])->name('update');
Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
