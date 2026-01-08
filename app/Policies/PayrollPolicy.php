<?php

namespace App\Policies;

use App\Models\Payroll;

class PayrollPolicy
{
    public function viewAny($user)
    {
        return $user->hasRole('Admin#' . session('business.id')) || $user->can('payroll.view');
    }

    public function view($user, Payroll $payroll = null)
    {
        return $user->hasRole('Admin#' . session('business.id')) || $user->can('payroll.view');
    }

    public function create($user)
    {
        return $user->hasRole('Admin#' . session('business.id')) || $user->can('payroll.create');
    }

    public function update($user, Payroll $payroll = null)
    {
        return $user->hasRole('Admin#' . session('business.id')) || $user->can('payroll.update');
    }

    public function delete($user, Payroll $payroll = null)
    {
        return $user->hasRole('Admin#' . session('business.id')) || $user->can('payroll.delete');
    }
}
