<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Leave;

class LeavePolicy
{
    public function viewAny($user)
    {
        return $user->hasRole('Admin#' . session('business.id')) || $user->can('leave.view');
    }

    public function view($user, Leave $leave = null)
    {
        return $user->hasRole('Admin#' . session('business.id')) || $user->can('leave.view');
    }

    public function create($user)
    {
        return $user->hasRole('Admin#' . session('business.id')) || $user->can('leave.create');
    }

    public function update($user, Leave $leave = null)
    {
        return $user->hasRole('Admin#' . session('business.id')) || $user->can('leave.update');
    }

    public function delete($user, Leave $leave = null)
    {
        return $user->hasRole('Admin#' . session('business.id')) || $user->can('leave.delete');
    }
}
