<?php

use Illuminate\Support\Facades\Route;
use Modules\Workflow\Http\Controllers\ApprovalPolicyController;
use Modules\Workflow\Http\Controllers\ApprovalRequestController;
use Modules\Workflow\Http\Controllers\AutomationRuleController;
use Modules\Workflow\Http\Controllers\BusinessRulesController;
use Modules\Workflow\Http\Controllers\ScheduledTaskController;
use Modules\Workflow\Http\Controllers\WorkflowDefinitionController;
use Modules\Workflow\Http\Controllers\WorkflowLogController;
use Modules\Workflow\Http\Controllers\WorkflowOverviewController;

Route::get('/overview', [WorkflowOverviewController::class, 'index'])->name('overview');

Route::prefix('workflows')->name('workflows.')->group(function () {
    Route::get('/', [WorkflowDefinitionController::class, 'index'])->name('index');
    Route::post('/', [WorkflowDefinitionController::class, 'store'])->name('store');
    Route::put('/{workflowDefinition}', [WorkflowDefinitionController::class, 'update'])->name('update');
    Route::delete('/{workflowDefinition}', [WorkflowDefinitionController::class, 'destroy'])->name('destroy');
});

Route::prefix('approval-requests')->name('approval-requests.')->group(function () {
    Route::get('/', [ApprovalRequestController::class, 'index'])->name('index');
    Route::post('/', [ApprovalRequestController::class, 'store'])->name('store');
    Route::put('/{approvalRequest}', [ApprovalRequestController::class, 'update'])->name('update');
    Route::delete('/{approvalRequest}', [ApprovalRequestController::class, 'destroy'])->name('destroy');
});

Route::prefix('approval-policies')->name('approval-policies.')->group(function () {
    Route::get('/', [ApprovalPolicyController::class, 'index'])->name('index');
    Route::post('/', [ApprovalPolicyController::class, 'store'])->name('store');
    Route::put('/{approvalPolicy}', [ApprovalPolicyController::class, 'update'])->name('update');
    Route::delete('/{approvalPolicy}', [ApprovalPolicyController::class, 'destroy'])->name('destroy');
});

Route::prefix('automation')->name('automation.')->group(function () {
    Route::get('/', [AutomationRuleController::class, 'index'])->name('index');
    Route::post('/', [AutomationRuleController::class, 'store'])->name('store');
    Route::put('/{automationRule}', [AutomationRuleController::class, 'update'])->name('update');
    Route::delete('/{automationRule}', [AutomationRuleController::class, 'destroy'])->name('destroy');
});

Route::get('/business-rules', [BusinessRulesController::class, 'index'])->name('business-rules');

Route::prefix('scheduled-tasks')->name('scheduled-tasks.')->group(function () {
    Route::get('/', [ScheduledTaskController::class, 'index'])->name('index');
    Route::post('/', [ScheduledTaskController::class, 'store'])->name('store');
    Route::put('/{scheduledTask}', [ScheduledTaskController::class, 'update'])->name('update');
    Route::delete('/{scheduledTask}', [ScheduledTaskController::class, 'destroy'])->name('destroy');
});

Route::prefix('logs')->name('logs.')->group(function () {
    Route::get('/', [WorkflowLogController::class, 'index'])->name('index');
    Route::post('/', [WorkflowLogController::class, 'store'])->name('store');
    Route::put('/{workflowLog}', [WorkflowLogController::class, 'update'])->name('update');
    Route::delete('/{workflowLog}', [WorkflowLogController::class, 'destroy'])->name('destroy');
});
