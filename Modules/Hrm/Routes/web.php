<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'module' => 'Hrm',
    'namespace' => 'Modules\\Hrm\\Http\\Controllers',
    // Ensure HRM routes build the admin sidebar and have session/lang context
    'middleware' => ['web', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu']
], function () {
    Route::get('/hrm', [\Modules\Hrm\Http\Controllers\HrmController::class, 'index']);

    // Departments
    Route::resource('/hrm/departments', \Modules\Hrm\Http\Controllers\DepartmentsController::class, [
        'as' => 'hrm'
    ]);

    // Companies
    Route::resource('/hrm/companies', \Modules\Hrm\Http\Controllers\CompanyController::class, [
        'as' => 'hrm'
    ]);

    // Designations
    Route::resource('/hrm/designations', \Modules\Hrm\Http\Controllers\DesignationsController::class, [
        'as' => 'hrm'
    ]);

    // Office Shifts
    Route::resource('/hrm/office_shifts', \Modules\Hrm\Http\Controllers\OfficeShiftController::class, [
        'as' => 'hrm'
    ]);

    // Payrolls
    Route::resource('/hrm/payrolls', \Modules\Hrm\Http\Controllers\PayrollController::class, [
        'as' => 'hrm'
    ]);

    // Helper: get employees by company for payroll form (AJAX)
    Route::get('/hrm/employees/by-company', [\Modules\Hrm\Http\Controllers\EmployeesController::class, 'Get_employees_by_company'])->name('hrm.employees.by_company');

    // Employees
    Route::resource('/hrm/employees', \Modules\Hrm\Http\Controllers\EmployeesController::class, [
        'as' => 'hrm'
    ]);

    // Deleted / trash view and restore/force-delete actions
    Route::get('/hrm/employees/trash', [\Modules\Hrm\Http\Controllers\EmployeesController::class, 'trash'])->name('hrm.employees.trash');
    Route::post('/hrm/employees/{employee}/restore', [\Modules\Hrm\Http\Controllers\EmployeesController::class, 'restore'])->name('hrm.employees.restore');
    Route::delete('/hrm/employees/{employee}/force-delete', [\Modules\Hrm\Http\Controllers\EmployeesController::class, 'forceDelete'])->name('hrm.employees.force_delete');

    // Employee supplemental actions: suspend and deductions
    Route::post('/hrm/employees/{employee}/suspend', [\Modules\Hrm\Http\Controllers\EmployeesController::class, 'suspend'])->name('hrm.employees.suspend');
    Route::get('/hrm/employees/{employee}/deductions', [\Modules\Hrm\Http\Controllers\EmployeesController::class, 'deductionsIndex'])->name('hrm.employees.deductions.index');
    Route::post('/hrm/employees/{employee}/deductions', [\Modules\Hrm\Http\Controllers\EmployeesController::class, 'deductionsStore'])->name('hrm.employees.deductions.store');
    Route::delete('/hrm/employees/{employee}/deductions/{deduction}', [\Modules\Hrm\Http\Controllers\EmployeesController::class, 'deductionsDestroy'])->name('hrm.employees.deductions.destroy');
});
