<?php

namespace App\Policies;

use App\Models\Holiday;

class HolidayPolicy
{
    public function viewAny($user)
    {
        return $user->hasRole('Admin#' . session('business.id'))
            || $user->can('holiday.view')
            || $user->can('hrm.holidays');
    }

    public function view($user, Holiday $holiday = null)
    {
        return $this->viewAny($user);
    }

    public function create($user)
    {
        return $user->hasRole('Admin#' . session('business.id'))
            || $user->can('holiday.create')
            || $user->can('hrm.holidays');
    }

    public function update($user, Holiday $holiday = null)
    {
        return $user->hasRole('Admin#' . session('business.id'))
            || $user->can('holiday.update')
            || $user->can('hrm.holidays');
    }

    public function delete($user, Holiday $holiday = null)
    {
        return $user->hasRole('Admin#' . session('business.id'))
            || $user->can('holiday.delete')
            || $user->can('hrm.holidays');
    }
}