<?php

namespace Modules\Hrm\Http\Controllers;

class DataController
{
    /**
     * Return HRM user permissions for Role UI.
     *
     * @return array
     */
    public function user_permissions()
    {
        return [
            [
                'value' => 'hrm.access',
                'label' => 'HRM Access',
                'default' => false,
            ],
            // Granular HRM feature permissions
            [
                'value' => 'hrm.companies',
                'label' => 'HRM: Companies',
                'default' => false,
            ],
            [
                'value' => 'hrm.departments',
                'label' => 'HRM: Departments',
                'default' => false,
            ],
            [
                'value' => 'hrm.designations',
                'label' => 'HRM: Designations',
                'default' => false,
            ],
            [
                'value' => 'hrm.office_shifts',
                'label' => 'HRM: Office Shifts',
                'default' => false,
            ],
            [
                'value' => 'hrm.employees',
                'label' => 'HRM: Employees',
                'default' => false,
            ],
            [
                'value' => 'hrm.payrolls',
                'label' => 'HRM: Payroll',
                'default' => false,
            ],
        ];
    }
}
