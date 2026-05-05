<?php

namespace Modules\Hrm\Http\Controllers;

class DataController
{
    /**
     * Return HRM permissions for the Role management UI.
     * These map 1:1 to Spatie permissions registered in HrmPermissionsSeeder.
     * Grouped into feature-level (coarse) and CRUD-level (fine) permissions.
     */
    public function user_permissions(): array
    {
        return [
            // ─── Feature-level (section access) ───────────────────────────
            [
                'value'   => 'hrm.access',
                'label'   => 'HRM: Full Access',
                'default' => false,
            ],
            [
                'value'   => 'hrm.companies',
                'label'   => 'HRM: Companies',
                'default' => false,
            ],
            [
                'value'   => 'hrm.departments',
                'label'   => 'HRM: Departments',
                'default' => false,
            ],
            [
                'value'   => 'hrm.designations',
                'label'   => 'HRM: Designations',
                'default' => false,
            ],
            [
                'value'   => 'hrm.office_shifts',
                'label'   => 'HRM: Office Shifts',
                'default' => false,
            ],
            [
                'value'   => 'hrm.employees',
                'label'   => 'HRM: Employees',
                'default' => false,
            ],
            [
                'value'   => 'hrm.payrolls',
                'label'   => 'HRM: Payroll',
                'default' => false,
            ],
            [
                'value'   => 'hrm.leaves',
                'label'   => 'HRM: Leave Management',
                'default' => false,
            ],
            [
                'value'   => 'hrm.attendances',
                'label'   => 'HRM: Attendance',
                'default' => false,
            ],
            [
                'value'   => 'hrm.holidays',
                'label'   => 'HRM: Holidays',
                'default' => false,
            ],
            [
                'value'   => 'hrm.settings',
                'label'   => 'HRM: Settings',
                'default' => false,
            ],

            // ─── CRUD-level (used by Laravel policies) ─────────────────────
            [
                'value'   => 'employee.view',
                'label'   => 'Employees: View',
                'default' => false,
            ],
            [
                'value'   => 'employee.create',
                'label'   => 'Employees: Create',
                'default' => false,
            ],
            [
                'value'   => 'employee.update',
                'label'   => 'Employees: Edit',
                'default' => false,
            ],
            [
                'value'   => 'employee.delete',
                'label'   => 'Employees: Delete',
                'default' => false,
            ],
            [
                'value'   => 'leave.view',
                'label'   => 'Leaves: View',
                'default' => false,
            ],
            [
                'value'   => 'leave.create',
                'label'   => 'Leaves: Create',
                'default' => false,
            ],
            [
                'value'   => 'leave.update',
                'label'   => 'Leaves: Edit / Approve',
                'default' => false,
            ],
            [
                'value'   => 'leave.delete',
                'label'   => 'Leaves: Delete',
                'default' => false,
            ],
            [
                'value'   => 'attendance.view',
                'label'   => 'Attendance: View',
                'default' => false,
            ],
            [
                'value'   => 'attendance.create',
                'label'   => 'Attendance: Clock In/Out',
                'default' => false,
            ],
            [
                'value'   => 'attendance.update',
                'label'   => 'Attendance: Edit',
                'default' => false,
            ],
            [
                'value'   => 'attendance.delete',
                'label'   => 'Attendance: Delete',
                'default' => false,
            ],
            [
                'value'   => 'payroll.view',
                'label'   => 'Payroll: View',
                'default' => false,
            ],
            [
                'value'   => 'payroll.create',
                'label'   => 'Payroll: Create',
                'default' => false,
            ],
            [
                'value'   => 'payroll.update',
                'label'   => 'Payroll: Edit',
                'default' => false,
            ],
            [
                'value'   => 'payroll.delete',
                'label'   => 'Payroll: Delete',
                'default' => false,
            ],
            [
                'value'   => 'holiday.view',
                'label'   => 'Holidays: View',
                'default' => false,
            ],
            [
                'value'   => 'holiday.create',
                'label'   => 'Holidays: Create',
                'default' => false,
            ],
            [
                'value'   => 'holiday.update',
                'label'   => 'Holidays: Edit',
                'default' => false,
            ],
            [
                'value'   => 'holiday.delete',
                'label'   => 'Holidays: Delete',
                'default' => false,
            ],
        ];
    }
}
