<?php

use Illuminate\Support\Facades\Route;
use Modules\Hrm\Http\Controllers\AttendanceController;
use Modules\Hrm\Http\Controllers\DepartmentController;
use Modules\Hrm\Http\Controllers\DesignationController;
use Modules\Hrm\Http\Controllers\EmployeeController;
use Modules\Hrm\Http\Controllers\HrmOverviewController;
use Modules\Hrm\Http\Controllers\HrmReportController;
use Modules\Hrm\Http\Controllers\LeaveController;
use Modules\Hrm\Http\Controllers\PayrollController;
use Modules\Hrm\Http\Controllers\SalaryStructureController;

Route::get('/overview', [HrmOverviewController::class, 'index'])->name('overview');
Route::get('/reports', [HrmReportController::class, 'index'])->name('reports');

Route::prefix('employees')->name('employees.')->group(function () {
    Route::get('/', [EmployeeController::class, 'index'])->name('index');
    Route::post('/', [EmployeeController::class, 'store'])->name('store');
    Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
    Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
});

Route::prefix('departments')->name('departments.')->group(function () {
    Route::get('/', [DepartmentController::class, 'index'])->name('index');
    Route::post('/', [DepartmentController::class, 'store'])->name('store');
    Route::put('/{department}', [DepartmentController::class, 'update'])->name('update');
    Route::delete('/{department}', [DepartmentController::class, 'destroy'])->name('destroy');
});

Route::prefix('designations')->name('designations.')->group(function () {
    Route::get('/', [DesignationController::class, 'index'])->name('index');
    Route::post('/', [DesignationController::class, 'store'])->name('store');
    Route::put('/{designation}', [DesignationController::class, 'update'])->name('update');
    Route::delete('/{designation}', [DesignationController::class, 'destroy'])->name('destroy');
});

Route::prefix('attendance')->name('attendance.')->group(function () {
    Route::get('/', [AttendanceController::class, 'index'])->name('index');
    Route::post('/', [AttendanceController::class, 'store'])->name('store');
    Route::put('/{attendance}', [AttendanceController::class, 'update'])->name('update');
    Route::delete('/{attendance}', [AttendanceController::class, 'destroy'])->name('destroy');
});

Route::prefix('leave')->name('leave.')->group(function () {
    Route::get('/', [LeaveController::class, 'index'])->name('index');
    Route::post('/', [LeaveController::class, 'store'])->name('store');
    Route::put('/{leave}', [LeaveController::class, 'update'])->name('update');
    Route::delete('/{leave}', [LeaveController::class, 'destroy'])->name('destroy');
});

Route::prefix('payroll')->name('payroll.')->group(function () {
    Route::get('/', [PayrollController::class, 'index'])->name('index');
    Route::post('/', [PayrollController::class, 'store'])->name('store');
    Route::put('/{payroll}', [PayrollController::class, 'update'])->name('update');
    Route::delete('/{payroll}', [PayrollController::class, 'destroy'])->name('destroy');
});

Route::prefix('salary-structure')->name('salary-structure.')->group(function () {
    Route::get('/', [SalaryStructureController::class, 'index'])->name('index');
    Route::post('/', [SalaryStructureController::class, 'store'])->name('store');
    Route::put('/{salaryStructure}', [SalaryStructureController::class, 'update'])->name('update');
    Route::delete('/{salaryStructure}', [SalaryStructureController::class, 'destroy'])->name('destroy');
});
