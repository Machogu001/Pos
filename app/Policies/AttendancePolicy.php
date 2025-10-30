<?php

namespace App\Policies;

use App\Models\Attendance;

class AttendancePolicy
{
    public function viewAny($user)
    {
        return $user->hasRole('Admin#' . session('business.id')) || $user->can('attendance.view');
    }

    public function view($user, Attendance $attendance = null)
    {
        return $user->hasRole('Admin#' . session('business.id')) || $user->can('attendance.view');
    }

    public function create($user)
    {
        return $user->hasRole('Admin#' . session('business.id')) || $user->can('attendance.create');
    }

    public function update($user, Attendance $attendance = null)
    {
        return $user->hasRole('Admin#' . session('business.id')) || $user->can('attendance.update');
    }

    public function delete($user, Attendance $attendance = null)
    {
        return $user->hasRole('Admin#' . session('business.id')) || $user->can('attendance.delete');
    }
}
