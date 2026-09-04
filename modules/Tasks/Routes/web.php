<?php

use Illuminate\Support\Facades\Route;
use Modules\Tasks\Http\Controllers\MyTaskController;
use Modules\Tasks\Http\Controllers\TaskCalendarController;
use Modules\Tasks\Http\Controllers\TaskController;
use Modules\Tasks\Http\Controllers\TaskCreateController;
use Modules\Tasks\Http\Controllers\TaskTimelineController;

Route::get('/my-tasks', [MyTaskController::class, 'index'])->name('my-tasks.index');
Route::get('/calendar', [TaskCalendarController::class, 'index'])->name('calendar');
Route::get('/timeline', [TaskTimelineController::class, 'index'])->name('timeline');
Route::get('/create', [TaskCreateController::class, 'create'])->name('create');
Route::post('/create', [TaskCreateController::class, 'store'])->name('create.store');

Route::prefix('all-tasks')->name('all-tasks.')->group(function () {
    Route::get('/', [TaskController::class, 'index'])->name('index');
    Route::post('/', [TaskController::class, 'store'])->name('store');
    Route::put('/{task}', [TaskController::class, 'update'])->name('update');
    Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
});
