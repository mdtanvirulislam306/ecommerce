<?php

use Illuminate\Support\Facades\Route;
use Modules\Files\Http\Controllers\AttachmentsController;
use Modules\Files\Http\Controllers\DocumentController;
use Modules\Files\Http\Controllers\MediaLibraryController;
use Modules\Files\Http\Controllers\StorageController;

Route::prefix('media-library')->name('media-library.')->group(function () {
    Route::get('/', [MediaLibraryController::class, 'index'])->name('index');
    Route::get('/picker', [MediaLibraryController::class, 'picker'])->name('picker');
    Route::post('/picker-upload', [MediaLibraryController::class, 'pickerUpload'])->name('picker-upload');
    Route::post('/', [MediaLibraryController::class, 'store'])->name('store');
    Route::delete('/{mediaLibraryItem}', [MediaLibraryController::class, 'destroy'])->name('destroy');
});

Route::prefix('documents')->name('documents.')->group(function () {
    Route::get('/', [DocumentController::class, 'index'])->name('index');
    Route::post('/', [DocumentController::class, 'store'])->name('store');
    Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
});

Route::get('/attachments', [AttachmentsController::class, 'index'])->name('attachments');
Route::get('/storage', [StorageController::class, 'index'])->name('storage');
