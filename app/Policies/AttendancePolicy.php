<?php

namespace App\Policies;

use App\Models\Attendance;

class AttendancePolicy
{
    public function viewAny($user)
    {
        return $user->hasRole('Admin#' . session('business.id'))
            || $user->can('attendance.view')
            || $user->can('hrm.attendances');
    }

    public function view($user, Attendance $attendance = null)
    {
        return $this->viewAny($user);
    }

    public function create($user)
    {
        return $user->hasRole('Admin#' . session('business.id'))
            || $user->can('attendance.create')
            || $user->can('hrm.attendances');
    }

    public function update($user, Attendance $attendance = null)
    {
        return $user->hasRole('Admin#' . session('business.id'))
            || $user->can('attendance.update')
            || $user->can('hrm.attendances');
    }

    public function delete($user, Attendance $attendance = null)
    {
        return $user->hasRole('Admin#' . session('business.id'))
            || $user->can('attendance.delete')
            || $user->can('hrm.attendances');
    }
}
