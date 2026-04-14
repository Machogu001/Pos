<?php

namespace App\Policies;

use App\Models\Payroll;

class PayrollPolicy
{
    protected function hasPayrollPermission($user, string $legacyPermission): bool
    {
        $mappedPermissions = [
            'payroll.view' => 'hrm.payrolls',
            'payroll.create' => 'hrm.payrolls',
            'payroll.update' => 'hrm.payrolls',
            'payroll.delete' => 'hrm.payrolls',
        ];

        return $user->hasRole('Admin#' . session('business.id'))
            || $user->can($legacyPermission)
            || $user->can($mappedPermissions[$legacyPermission] ?? 'hrm.payrolls')
            || $user->can('hrm.access');
    }

    public function viewAny($user)
    {
        return $this->hasPayrollPermission($user, 'payroll.view');
    }

    public function view($user, Payroll $payroll = null)
    {
        return $this->hasPayrollPermission($user, 'payroll.view');
    }

    public function create($user)
    {
        return $this->hasPayrollPermission($user, 'payroll.create');
    }

    public function update($user, Payroll $payroll = null)
    {
        return $this->hasPayrollPermission($user, 'payroll.update');
    }

    public function delete($user, Payroll $payroll = null)
    {
        return $this->hasPayrollPermission($user, 'payroll.delete');
    }
}
