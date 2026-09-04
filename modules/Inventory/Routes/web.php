<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\InventoryOverviewController;
use Modules\Inventory\Http\Controllers\InventoryTrackingController;
use Modules\Inventory\Http\Controllers\StockController;
use Modules\Inventory\Http\Controllers\StockTransferController;
use Modules\Inventory\Http\Controllers\WarehouseController;

Route::get('/overview', [InventoryOverviewController::class, 'index'])->name('overview');

Route::prefix('warehouses')->name('warehouses.')->group(function () {
    Route::get('/', [WarehouseController::class, 'index'])->name('index');
    Route::post('/', [WarehouseController::class, 'store'])->name('store');
    Route::put('/{warehouse}', [WarehouseController::class, 'update'])->name('update');
    Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])->name('destroy');
});

Route::get('/stock-overview', [StockController::class, 'overview'])->name('stock-overview.index');
Route::get('/low-stock', [StockController::class, 'overview'])->name('low-stock.index');
Route::get('/out-of-stock', [StockController::class, 'overview'])->name('out-of-stock.index');

Route::get('/stock-movement', [StockController::class, 'movements'])->name('stock-movement.index');
Route::get('/stock-adjustment', [StockController::class, 'adjustmentForm'])->name('stock-adjustment.index');
Route::get('/stock-adjustment/create', [StockController::class, 'adjustmentForm'])->name('stock-adjustment.create');
Route::post('/stock-adjustment', [StockController::class, 'adjust'])->name('stock-adjustment.store');

Route::prefix('stock-transfer')->name('stock-transfer.')->group(function () {
    Route::get('/', [StockTransferController::class, 'index'])->name('index');
    Route::get('/create', [StockTransferController::class, 'create'])->name('create');
    Route::post('/', [StockTransferController::class, 'store'])->name('store');
});

Route::get('/batches', [InventoryTrackingController::class, 'batches'])->name('batches.index');
Route::post('/batches', [InventoryTrackingController::class, 'storeBatch'])->name('batches.store');
Route::put('/batches/{inventoryBatch}', [InventoryTrackingController::class, 'updateBatch'])->name('batches.update');
Route::delete('/batches/{inventoryBatch}', [InventoryTrackingController::class, 'destroyBatch'])->name('batches.destroy');

Route::get('/serial-numbers', [InventoryTrackingController::class, 'serials'])->name('serial-numbers.index');
Route::post('/serial-numbers', [InventoryTrackingController::class, 'storeSerial'])->name('serial-numbers.store');
Route::put('/serial-numbers/{inventorySerialNumber}', [InventoryTrackingController::class, 'updateSerial'])->name('serial-numbers.update');
Route::delete('/serial-numbers/{inventorySerialNumber}', [InventoryTrackingController::class, 'destroySerial'])->name('serial-numbers.destroy');

Route::get('/stock-valuation', [InventoryTrackingController::class, 'valuation'])->name('stock-valuation.index');
Route::get('/reports', [InventoryTrackingController::class, 'reports'])->name('reports.index');

Route::get('/products/{productId}/variants', [StockController::class, 'productVariants'])
    ->name('product-variants')
    ->whereNumber('productId');
