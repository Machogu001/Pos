<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Debug utilities: only load in local/dev to avoid exposing in production
Route::group([
    'module' => 'Hrm',
    'namespace' => 'Modules\\Hrm\\Http\\Controllers',
    'middleware' => ['web', 'auth', 'SetSessionData', 'language', 'timezone']
], function () {
    if (app()->environment('local')) {
        Route::get('/hrm/debug/access', function () {
            $user = auth()->user();
            $roles = $user ? $user->getRoleNames() : collect();
            $perms = $user ? $user->getPermissionNames() : collect();
            $enabled = session('business.enabled_modules') ?? [];
            return response()->json([
                'user_id' => $user ? $user->id : null,
                'business_id' => $user ? $user->business_id : null,
                'roles' => $roles,
                'permissions' => $perms,
                'hrm_enabled' => in_array('hrm', (array) $enabled),
                'enabled_modules' => $enabled,
            ]);
        })->name('hrm.debug.access');

        // Temporary: seed granular HRM permissions if missing
        Route::post('/hrm/debug/seed-perms', function () {
            $perms = [
                'hrm.access',
                'hrm.companies',
                'hrm.departments',
                'hrm.designations',
                'hrm.office_shifts',
                'hrm.employees',
                'hrm.payrolls',
            ];
            foreach ($perms as $p) {
                \Spatie\Permission\Models\Permission::firstOrCreate([
                    'name' => $p,
                    'guard_name' => 'web',
                ]);
            }
            return response()->json(['ok' => true, 'seeded' => $perms]);
        })->name('hrm.debug.seed_perms');
    }
});

Route::group([
    'module' => 'Hrm',
    'namespace' => 'Modules\\Hrm\\Http\\Controllers',
    // Ensure HRM routes build the admin sidebar and have session/lang context
    // Log access attempts, rely on controller-level granular permissions.
    'middleware' => ['web', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'subscription']
], function () {
    // Canonical HRM entrypoint: always use /hrm/dashboard.
    Route::get('/hrm', function () {
        return redirect('/hrm/dashboard');
    });
    Route::get('/hrm/reports', [\Modules\Hrm\Http\Controllers\ReportsController::class, 'index'])->name('hrm.reports.index');
    Route::get('/hrm/reports/export/{section}/{format}', [\Modules\Hrm\Http\Controllers\ReportsController::class, 'export'])->name('hrm.reports.export');

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

    // Temporary debug endpoints for POST/proxy issues.
    // Only register them in local environment to keep production clean.
    if (app()->environment('local')) {
        Route::post('/hrm/companies/debug', function (Request $request) {
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
    }

    // Designations
    Route::resource('/hrm/designations', \Modules\Hrm\Http\Controllers\DesignationsController::class, [
        'as' => 'hrm'
    ]);

    // Office Shifts
    Route::resource('/hrm/office_shifts', \Modules\Hrm\Http\Controllers\OfficeShiftController::class, [
        'as' => 'hrm'
    ]);

    // Attendances
    Route::resource('/hrm/attendances', \Modules\Hrm\Http\Controllers\AttendancesController::class, [
        'as' => 'hrm'
    ]);

    // Holidays
    Route::resource('/hrm/holidays', \Modules\Hrm\Http\Controllers\HolidayController::class, [
        'as' => 'hrm'
    ]);

    // Payrolls — P9 must be declared before the resource to avoid route shadowing
    Route::get('/hrm/payrolls/p9', [\Modules\Hrm\Http\Controllers\PayrollController::class, 'p9'])->name('hrm.payrolls.p9');
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

    // Leaves (Leave management)
    Route::resource('/hrm/leaves', \Modules\Hrm\Http\Controllers\LeaveController::class, [
        'as' => 'hrm'
    ]);

    // Serve leave attachments through auth-protected route (files stored in private storage)
    Route::get('/hrm/leaves/{id}/attachment', [\Modules\Hrm\Http\Controllers\LeaveController::class, 'attachment'])
        ->name('hrm.leaves.attachment');

    // HRM Settings (default leave)
    Route::get('/hrm/settings', [\Modules\Hrm\Http\Controllers\SettingsController::class, 'index'])->name('hrm.settings.index');
    Route::get('/hrm/settings/leave', [\Modules\Hrm\Http\Controllers\SettingsController::class, 'editDefaultLeave'])->name('hrm.settings.leave.edit');
    Route::post('/hrm/settings/leave', [\Modules\Hrm\Http\Controllers\SettingsController::class, 'updateDefaultLeave'])->name('hrm.settings.leave.update');

    // HRM Settings: Enable/Disable core modules
    Route::get('/hrm/settings/modules', [\Modules\Hrm\Http\Controllers\SettingsController::class, 'editModules'])->name('hrm.settings.modules.edit');
    Route::post('/hrm/settings/modules', [\Modules\Hrm\Http\Controllers\SettingsController::class, 'updateModules'])->name('hrm.settings.modules.update');

    // HRM Settings: Payroll posting accounts
    Route::get('/hrm/settings/payroll', [\Modules\Hrm\Http\Controllers\SettingsController::class, 'editPayrollPosting'])->name('hrm.settings.payroll.edit');
    Route::post('/hrm/settings/payroll', [\Modules\Hrm\Http\Controllers\SettingsController::class, 'updatePayrollPosting'])->name('hrm.settings.payroll.update');

    // HRM Settings: Theme selection
    Route::get('/hrm/settings/theme', [\Modules\Hrm\Http\Controllers\SettingsController::class, 'editTheme'])->name('hrm.settings.theme.edit');
    Route::post('/hrm/settings/theme', [\Modules\Hrm\Http\Controllers\SettingsController::class, 'updateTheme'])->name('hrm.settings.theme.update');

    // Leave Types
    Route::resource('/hrm/leave_types', \Modules\Hrm\Http\Controllers\LeaveTypeController::class, [
        'as' => 'hrm'
    ]);

    // install / uninstall routes are registered in the main routes/web.php
});
