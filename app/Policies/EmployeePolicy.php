<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Employee;

class EmployeePolicy
{
    protected function hasEmployeePermission($user, string $legacyPermission): bool
    {
        $mappedPermissions = [
            'employee.view' => 'hrm.employees',
            'employee.create' => 'hrm.employees',
            'employee.update' => 'hrm.employees',
            'employee.delete' => 'hrm.employees',
        ];

        return $this->isAdmin($user)
            || $user->can($legacyPermission)
            || $user->can($mappedPermissions[$legacyPermission] ?? 'hrm.employees')
            || $user->can('hrm.access');
    }

    /**
     * Determine whether the user can view any employees.
     */
    public function viewAny($user)
    {
        return $this->hasEmployeePermission($user, 'employee.view');
    }

    /**
     * Determine whether the user can view the employee.
     */
    public function view($user, Employee $employee = null)
    {
        return $this->hasEmployeePermission($user, 'employee.view');
    }

    /**
     * Determine whether the user can create employees.
     */
    public function create($user)
    {
        return $this->hasEmployeePermission($user, 'employee.create');
    }

    /**
     * Determine whether the user can update the employee.
     */
    public function update($user, Employee $employee = null)
    {
        return $this->hasEmployeePermission($user, 'employee.update');
    }

    /**
     * Determine whether the user can delete the employee.
     */
    public function delete($user, Employee $employee = null)
    {
        return $this->hasEmployeePermission($user, 'employee.delete');
    }

    /**
     * Helper: consider business admin as allowed for HRM actions
     */
    protected function isAdmin($user)
    {
        try {
            return $user->hasRole('Admin#' . session('business.id'));
        } catch (\Exception $e) {
            return false;
        }
    }
}
