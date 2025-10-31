<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

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
    // Department head management (set/remove head via AJAX or form)
    Route::post('/hrm/departments/{department}/head', [\Modules\Hrm\Http\Controllers\DepartmentsController::class, 'setHead'])->name('hrm.departments.set_head');
    Route::delete('/hrm/departments/{department}/head', [\Modules\Hrm\Http\Controllers\DepartmentsController::class, 'removeHead'])->name('hrm.departments.remove_head');

    // Companies
    Route::resource('/hrm/companies', \Modules\Hrm\Http\Controllers\CompanyController::class, [
        'as' => 'hrm'
    ]);

    // Temporary debug endpoint to help diagnose POST/proxy issues.
    // Logs request headers and body and returns JSON. Remove after debugging.
    Route::post('/hrm/companies/debug', function (Request $request) {
        // Log headers and body for inspection
        try {
            $headers = [];
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
            }
        } catch (\Throwable $e) {
            $headers = [];
        }
        logger()->info('HRM debug POST received', ['headers' => $headers, 'body' => $request->all()]);
        return response()->json(['ok' => true, 'received' => $request->all()]);
    })->name('hrm.companies.debug');

    // Additional debug endpoints for other HRM resources (temporary)
    Route::post('/hrm/departments/debug', function (Request $request) {
        try { $headers = function_exists('getallheaders') ? getallheaders() : []; } catch (\Throwable $e) { $headers = []; }
        logger()->info('HRM debug POST received - departments', ['headers' => $headers, 'body' => $request->all()]);
        return response()->json(['ok' => true, 'received' => $request->all()]);
    })->name('hrm.departments.debug');

    Route::post('/hrm/designations/debug', function (Request $request) {
        try { $headers = function_exists('getallheaders') ? getallheaders() : []; } catch (\Throwable $e) { $headers = []; }
        logger()->info('HRM debug POST received - designations', ['headers' => $headers, 'body' => $request->all()]);
        return response()->json(['ok' => true, 'received' => $request->all()]);
    })->name('hrm.designations.debug');

    Route::post('/hrm/office_shifts/debug', function (Request $request) {
        try { $headers = function_exists('getallheaders') ? getallheaders() : []; } catch (\Throwable $e) { $headers = []; }
        logger()->info('HRM debug POST received - office_shifts', ['headers' => $headers, 'body' => $request->all()]);
        return response()->json(['ok' => true, 'received' => $request->all()]);
    })->name('hrm.office_shifts.debug');

    Route::post('/hrm/employees/debug', function (Request $request) {
        try { $headers = function_exists('getallheaders') ? getallheaders() : []; } catch (\Throwable $e) { $headers = []; }
        logger()->info('HRM debug POST received - employees', ['headers' => $headers, 'body' => $request->all()]);
        return response()->json(['ok' => true, 'received' => $request->all()]);
    })->name('hrm.employees.debug');

    Route::post('/hrm/payrolls/debug', function (Request $request) {
        try { $headers = function_exists('getallheaders') ? getallheaders() : []; } catch (\Throwable $e) { $headers = []; }
        logger()->info('HRM debug POST received - payrolls', ['headers' => $headers, 'body' => $request->all()]);
        return response()->json(['ok' => true, 'received' => $request->all()]);
    })->name('hrm.payrolls.debug');

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
