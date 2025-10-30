<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Employee;

class EmployeePolicy
{
    /**
     * Determine whether the user can view any employees.
     */
    public function viewAny($user)
    {
        return $this->isAdmin($user) || $user->can('employee.view');
    }

    /**
     * Determine whether the user can view the employee.
     */
    public function view($user, Employee $employee = null)
    {
        return $this->isAdmin($user) || $user->can('employee.view');
    }

    /**
     * Determine whether the user can create employees.
     */
    public function create($user)
    {
        return $this->isAdmin($user) || $user->can('employee.create');
    }

    /**
     * Determine whether the user can update the employee.
     */
    public function update($user, Employee $employee = null)
    {
        return $this->isAdmin($user) || $user->can('employee.update');
    }

    /**
     * Determine whether the user can delete the employee.
     */
    public function delete($user, Employee $employee = null)
    {
        return $this->isAdmin($user) || $user->can('employee.delete');
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
