<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\EmailChannelController;
use Modules\Notifications\Http\Controllers\NotificationCenterController;
use Modules\Notifications\Http\Controllers\NotificationTemplateController;
use Modules\Notifications\Http\Controllers\PushChannelController;
use Modules\Notifications\Http\Controllers\SmsChannelController;
use Modules\Notifications\Http\Controllers\WhatsappChannelController;

Route::get('/center', [NotificationCenterController::class, 'index'])->name('center');
Route::post('/center/{userNotification}/read', [NotificationCenterController::class, 'markRead'])->name('center.read');

Route::prefix('templates')->name('templates.')->group(function () {
    Route::get('/', [NotificationTemplateController::class, 'index'])->name('index');
    Route::post('/', [NotificationTemplateController::class, 'store'])->name('store');
    Route::put('/{notificationTemplate}', [NotificationTemplateController::class, 'update'])->name('update');
    Route::delete('/{notificationTemplate}', [NotificationTemplateController::class, 'destroy'])->name('destroy');
});

Route::get('/email', [EmailChannelController::class, 'edit'])->name('email.edit');
Route::put('/email', [EmailChannelController::class, 'update'])->name('email.update');
Route::get('/sms', [SmsChannelController::class, 'edit'])->name('sms.edit');
Route::put('/sms', [SmsChannelController::class, 'update'])->name('sms.update');
Route::get('/whatsapp', [WhatsappChannelController::class, 'edit'])->name('whatsapp.edit');
Route::put('/whatsapp', [WhatsappChannelController::class, 'update'])->name('whatsapp.update');
Route::get('/push', [PushChannelController::class, 'edit'])->name('push.edit');
Route::put('/push', [PushChannelController::class, 'update'])->name('push.update');
