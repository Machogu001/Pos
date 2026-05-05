<?php

namespace Modules\Hrm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\OfficeShift;
use Illuminate\Http\Request;

/**
 * Internal AJAX helpers — returns dependent-dropdown data.
 * Every method requires hrm.access or the relevant feature permission.
 */
class CoreController extends Controller
{
    protected function authorizeHrm(Request $request): void
    {
        $user = $request->user('api') ?? $request->user() ?? auth()->user();

        if (! $user || (
            ! $user->can('hrm.access') &&
            ! $user->can('hrm.employees') &&
            ! $user->can('hrm.departments') &&
            ! $user->can('hrm.designations') &&
            ! $user->can('hrm.office_shifts')
        )) {
            abort(403);
        }
    }

    public function Get_designations_by_department(Request $request)
    {
        $this->authorizeHrm($request);

        $designations = Designation::where('department_id', $request->id)
            ->whereNull('deleted_at')
            ->get(['id', 'designation']);

        return response()->json($designations);
    }

    public function Get_departments_by_company(Request $request)
    {
        $this->authorizeHrm($request);

        $departments = Department::where('company_id', $request->id)
            ->whereNull('deleted_at')
            ->get(['id', 'department']);

        return response()->json($departments);
    }

    public function Get_office_shift_by_company(Request $request)
    {
        $this->authorizeHrm($request);

        $office_shifts = OfficeShift::where('company_id', $request->id)
            ->whereNull('deleted_at')
            ->get(['id', 'name']);

        return response()->json($office_shifts);
    }

    public function Get_employees_by_company(Request $request)
    {
        $this->authorizeHrm($request);

        $employees = Employee::where('company_id', $request->id)
            ->whereNull('deleted_at')
            ->get(['id', 'username', 'firstname', 'lastname']);

        return response()->json($employees);
    }
}
