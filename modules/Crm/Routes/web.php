<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Crm\Http\Controllers\ActivityController;
use Modules\Crm\Http\Controllers\CrmOverviewController;
use Modules\Crm\Http\Controllers\CrmReportController;
use Modules\Crm\Http\Controllers\CustomerController;
use Modules\Crm\Http\Controllers\CustomerSegmentController;
use Modules\Crm\Http\Controllers\LeadController;
use Modules\Crm\Http\Controllers\LeadSourceController;

Route::get('/overview', [CrmOverviewController::class, 'index'])->name('overview');
Route::get('/reports', [CrmReportController::class, 'index'])->name('reports');

Route::prefix('customers')->name('customers.')->group(function () {
    Route::get('/', fn () => redirect()->route('crm.customers.all'))->name('index');
    Route::get('/all', [CustomerController::class, 'index'])->name('all');
    Route::get('/create', [CustomerController::class, 'create'])->name('create');
    Route::get('/segments', [CustomerSegmentController::class, 'index'])->name('segments');
    Route::post('/segments', [CustomerSegmentController::class, 'store'])->name('segments.store');
    Route::put('/segments/{customerSegment}', [CustomerSegmentController::class, 'update'])->name('segments.update');
    Route::delete('/segments/{customerSegment}', [CustomerSegmentController::class, 'destroy'])->name('segments.destroy');
    Route::post('/', [CustomerController::class, 'store'])->name('store');
    Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
    Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
    Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
});

Route::prefix('leads')->name('leads.')->group(function () {
    Route::get('/', fn () => redirect()->route('crm.leads.all'))->name('index');
    Route::get('/export', [LeadController::class, 'export'])->name('export');
    Route::get('/my/export', function (Request $request) {
        return redirect()->route('crm.leads.export', [
            ...$request->query(),
            'scope' => 'mine',
        ]);
    })->name('my.export');
    Route::get('/pipeline/export', function (Request $request) {
        return redirect()->route('crm.leads.export', [
            ...$request->query(),
            'view' => 'pipeline',
        ]);
    })->name('pipeline.export');
    Route::get('/all', [LeadController::class, 'index'])->name('all');
    Route::get('/my', [LeadController::class, 'my'])->name('my');
    Route::get('/pipeline', [LeadController::class, 'pipeline'])->name('pipeline');
    Route::get('/create', [LeadController::class, 'create'])->name('create');
    Route::get('/sources', [LeadSourceController::class, 'index'])->name('sources');
    Route::post('/sources', [LeadSourceController::class, 'store'])->name('sources.store');
    Route::post('/sources/quick', [LeadSourceController::class, 'quickStore'])->name('sources.quick');
    Route::put('/sources/{leadSource}', [LeadSourceController::class, 'update'])->name('sources.update');
    Route::delete('/sources/{leadSource}', [LeadSourceController::class, 'destroy'])->name('sources.destroy');
    Route::post('/', [LeadController::class, 'store'])->name('store');
    Route::put('/{lead}', [LeadController::class, 'update'])->name('update');
    Route::post('/{lead}/stage', [LeadController::class, 'moveStage'])->name('stage');
    Route::post('/{lead}/convert', [LeadController::class, 'convert'])->name('convert');
    Route::delete('/{lead}', [LeadController::class, 'destroy'])->name('destroy');
});

Route::prefix('activities')->name('activities.')->group(function () {
    Route::get('/', fn () => redirect()->route('crm.activities.all'))->name('index');
    Route::get('/all', [ActivityController::class, 'index'])->name('all');
    Route::get('/follow-ups', [ActivityController::class, 'index'])->name('follow-ups');
    Route::get('/calendar', [ActivityController::class, 'calendar'])->name('calendar');
    Route::post('/', [ActivityController::class, 'store'])->name('store');
    Route::post('/{crmActivity}/complete', [ActivityController::class, 'complete'])->name('complete');
    Route::delete('/{crmActivity}', [ActivityController::class, 'destroy'])->name('destroy');
});
