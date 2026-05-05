<?php

namespace Modules\Hrm\Http\Controllers;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveType;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Hrm\Http\Controllers\Concerns\AuditsHrmActions;
use Modules\Hrm\Http\Requests\StoreLeaveRequest;
use Modules\Hrm\Http\Requests\UpdateLeaveRequest;

class LeaveController extends Controller
{
    use AuditsHrmActions;


    protected function getAuthUser($request)
    {
        // Prefer api user if present (for API calls), otherwise fall back to web user
        return $request->user('api') ?? $request->user() ?? auth()->user();
    }

    //----------- GET ALL Leaves --------------\\

    public function index(Request $request)
    {
    // Canonical web entrypoint for leave management is the Essentials flow.
    if (! $request->wantsJson() && ! $request->expectsJson()) {
        $target = url('/hrm/leave');
        if ($request->getQueryString()) {
            $target .= '?' . $request->getQueryString();
        }

        return redirect($target);
    }

    $this->authorizeForUser($this->getAuthUser($request), 'view', Leave::class);

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number; only computed when perPage is numeric
        $offSet = 0;
        if (is_numeric($perPage) && intval($perPage) > 0) {
            $offSet = ($pageStart * intval($perPage)) - intval($perPage);
        }

        // sanitize ordering inputs
        $order = $request->SortField;
        $dir = $request->SortType;
        if (!in_array(strtolower($dir ?? ''), ['asc', 'desc'])) {
            $dir = 'desc';
        }
        $allowed = ['id', 'start_date', 'end_date', 'created_at', 'updated_at', 'days'];
        if (empty($order) || !in_array($order, $allowed)) {
            $order = 'id';
        }

        // Build base query defensively: this installation may store company/department on
        // employees even when those columns do not exist on leaves.
        $leaves = Leave::query();
        $searchColumns = [];
        $companyFilterColumn = null;
        $hasEmployeeJoin = false;

        // employees (employee_id expected to exist)
        if (Schema::hasTable('employees') && Schema::hasColumn('leaves', 'employee_id')) {
            $leaves = $leaves->join('employees', 'employees.id', '=', 'leaves.employee_id')
                ->selectRaw('leaves.*, employees.username AS employee_name, employees.id AS employee_id');
            $searchColumns[] = 'employees.username';
            $hasEmployeeJoin = true;
        } else {
            $leaves = $leaves->select('leaves.*');
        }

        // leave types (optional)
        if (Schema::hasTable('leave_types') && Schema::hasColumn('leaves', 'leave_type_id')) {
            $labelCol = Schema::hasColumn('leave_types', 'name') ? 'name' : (Schema::hasColumn('leave_types', 'title') ? 'title' : null);
            if ($labelCol) {
                $leaves = $leaves->leftJoin('leave_types', 'leave_types.id', '=', 'leaves.leave_type_id')
                    ->selectRaw("leave_types.{$labelCol} AS leave_type_title, leave_types.id AS leave_type_id");
                $searchColumns[] = "leave_types.{$labelCol}";
            }
        }

        // companies (optional)
        if (Schema::hasTable('companies')) {
            if (Schema::hasColumn('leaves', 'company_id')) {
                $leaves = $leaves->leftJoin('companies', 'companies.id', '=', 'leaves.company_id')
                    ->selectRaw('companies.name AS company_name, companies.id AS company_id');
                $companyFilterColumn = 'leaves.company_id';
                $searchColumns[] = 'companies.name';
            } elseif ($hasEmployeeJoin && Schema::hasColumn('employees', 'company_id')) {
                $leaves = $leaves->leftJoin('companies', 'companies.id', '=', 'employees.company_id')
                    ->selectRaw('companies.name AS company_name, companies.id AS company_id');
                $companyFilterColumn = 'employees.company_id';
                $searchColumns[] = 'companies.name';
            }
        } elseif ($hasEmployeeJoin && Schema::hasColumn('employees', 'company_id')) {
            $companyFilterColumn = 'employees.company_id';
        }

        // departments (optional)
        if (Schema::hasTable('departments')) {
            if (Schema::hasColumn('leaves', 'department_id')) {
                $leaves = $leaves->leftJoin('departments', 'departments.id', '=', 'leaves.department_id')
                    ->selectRaw('departments.department AS department_name, departments.id AS department_id');
                $searchColumns[] = 'departments.department';
            } elseif ($hasEmployeeJoin && Schema::hasColumn('employees', 'department_id')) {
                $leaves = $leaves->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
                    ->selectRaw('departments.department AS department_name, departments.id AS department_id');
                $searchColumns[] = 'departments.department';
            }
        }

        $leaves = $leaves->whereNull('leaves.deleted_at')
            ->when($request->filled('search') && !empty($searchColumns), function ($query) use ($request, $searchColumns) {
                $query->where(function ($searchQuery) use ($request, $searchColumns) {
                    foreach ($searchColumns as $column) {
                        $searchQuery->orWhere($column, 'LIKE', "%{$request->search}%");
                    }
                });
            });

        // Apply company filter if provided (AJAX/browser filters)
        if ($request->filled('company_id') && $companyFilterColumn) {
            $leaves = $leaves->where($companyFilterColumn, $request->get('company_id'));
        }

        $totalRows = $leaves->count();
        if ($perPage == "-1") {
            $perPage = $totalRows;
        }

        // If caller expects JSON (AJAX/API) we return flattened JSON (existing behavior)
        if ($request->wantsJson() || $request->expectsJson()) {
            if (is_numeric($perPage) && intval($perPage) > 0) {
                $items = $leaves->offset($offSet)
                    ->limit(intval($perPage))
                    ->orderBy($order, $dir)
                    ->get();
            } else {
                $items = $leaves->orderBy($order, $dir)->get();
            }

            return response()->json([
                'leaves' => $items,
                'totalRows' => $totalRows,
            ]);
        }

        // For regular browser requests, use LengthAwarePaginator so Blade can render pagination links
        $perPageForPaginator = (string)$request->limit === '-1' ? ($totalRows > 0 ? $totalRows : 1) : (is_numeric($perPage) && intval($perPage) > 0 ? intval($perPage) : 10);
        $pageStart = max(1, (int) request()->get('page', 1));

        $paginator = $leaves->orderBy($order, $dir)->paginate($perPageForPaginator, ['*'], 'page', $pageStart);

        // Map items into a simple array for the view while keeping the paginator for links
        $data = [];
        foreach ($paginator->items() as $l) {
            $data[] = [
                'id' => $l->id,
                'employee_name' => $l->employee_name ?? ($l->employee_id ?? ''),
                'leave_type_title' => $l->leave_type_title ?? '-',
                'start_date' => $l->start_date ?? '',
                'end_date' => $l->end_date ?? '',
                'days' => $l->days ?? 0,
                'status' => $l->status ?? '',
                'company_name' => $l->company_name ?? '-',
                'department_name' => $l->department_name ?? '-',
            ];
        }

        // Choose businesses/companies for the filter dropdown
        if (Schema::hasTable('business')) {
            $companies = \App\Business::orderBy('id', 'desc')->get(['id','name']);
        } else {
            $companies = Schema::hasTable('companies') ? Company::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']) : collect([]);
        }

        return view('hrm::leaves.index', [
            'leaves' => $data,
            'totalRows' => $totalRows,
            'paginator' => $paginator,
            'companies' => $companies,
        ]);
    }


    public function create(Request $request)
    {
    $this->authorizeForUser($this->getAuthUser($request), 'create', Leave::class);

        $leave_types = LeaveType::where('deleted_at', '=', null)->orderBy('id', 'desc')->get();
        $companies = Company::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']);
    $departments = Schema::hasTable('departments') ? Department::where('deleted_at', '=', null)->orderBy('id','desc')->get(['id','department','company_id']) : collect([]);

        // Return JSON for AJAX/API clients
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'companies'   => $companies,
                'leave_types' => $leave_types,
                'departments' => $departments,
            ]);
        }

        // For regular browser requests, render the create blade and pass metadata
        return view('hrm::leaves.create', [
            'companies' => $companies,
            'leave_types' => $leave_types,
            'departments' => $departments,
        ]);

    }


    //----------- Store new Leave --------------\\

    public function store(StoreLeaveRequest $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'create', Leave::class);

        // Secure file storage: private path, UUID filename, not publicly guessable
        $storedPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $storedPath = $file->storeAs(
                'hrm/leaves',
                Str::uuid().'.'.$file->extension(),
                'local'
            );
        }

        $start_date = new DateTime($request->start_date);
        $end_date   = new DateTime($request->end_date);
        // Fix: use ->days (total days) not ->d (days-remainder after months)
        $days_diff  = $start_date->diff($end_date)->days + 1;

        $leave_data = [
            'employee_id'   => $request->employee_id,
            'company_id'    => $request->company_id,
            'department_id' => $request->department_id,
            'leave_type_id' => $request->leave_type_id,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'days'          => $days_diff,
            'reason'        => $request->reason,
            'attachment'    => $storedPath,
            'half_day'      => (bool) $request->half_day,
            'status'        => $request->status,
        ];

        $employee_leave_info = Employee::find($request->employee_id);
        if (! $employee_leave_info) {
            return response()->json(['error' => 'Employee not found', 'isvalid' => false], 404);
        }

        $defaultLeave = config('hrm.default_annual_leave', 21);
        $empRemaining = intval($employee_leave_info->remaining_leave ?? $defaultLeave);

        if ($days_diff > $empRemaining) {
            // Clean up uploaded file before returning the error
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }
            return response()->json(['remaining_leave' => 'Remaining leave balance is insufficient.', 'isvalid' => false]);
        }

        if ($request->status === 'approved') {
            $before = $empRemaining;
            $after  = max(0, $empRemaining - $days_diff);
            $employee_leave_info->remaining_leave = $after;
            $employee_leave_info->save();
            logger()->channel('stack')->info('hrm.leave.approved', [
                'employee_id' => $employee_leave_info->id,
                'leave_days'  => $days_diff,
                'balance_before' => $before,
                'balance_after'  => $after,
                'action'      => 'approve_create',
                'actor_id'    => auth()->id(),
            ]);
        }

        $leave = Leave::create($leave_data);

        $this->logHrmAudit('hrm.leave.created', [
            'leave_id' => $leave->id,
            'employee_id' => $leave->employee_id,
            'company_id' => $leave->company_id,
            'department_id' => $leave->department_id,
            'status' => $leave->status,
            'start_date' => $leave->start_date,
            'end_date' => $leave->end_date,
            'days' => $leave->days,
        ], $leave);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true, 'isvalid' => true]);
        }
        return redirect()->route('hrm.leaves.index')->with('success', 'Leave created successfully.');
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
    }


    public function edit(Request $request, $id)
    {
    $this->authorizeForUser($this->getAuthUser($request), 'update', Leave::class);

        $leave = Leave::where('deleted_at', '=', null)->findOrFail($id);
        $leave_types = LeaveType::where('deleted_at', '=', null)->orderBy('id', 'desc')->get();
        $companies = Company::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']);
        $departments = Schema::hasTable('departments') ? Department::where('deleted_at', '=', null)->orderBy('id','desc')->get(['id','department','company_id']) : collect([]);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'leave'       => $leave,
                'companies'   => $companies,
                'leave_types' => $leave_types,
                'departments' => $departments,
            ]);
        }

        return view('hrm::leaves.edit', [
            'leave' => $leave,
            'companies' => $companies,
            'leave_types' => $leave_types,
            'departments' => $departments,
        ]);

    }


    //-----------Update Leave --------------\\

    public function update(UpdateLeaveRequest $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'update', Leave::class);

        $leave = Leave::findOrFail($id);

        // Secure file replacement: delete old private file, store new one
        $storedPath = $leave->attachment;
        if ($request->hasFile('attachment')) {
            // Remove previous private file if it exists
            if ($storedPath && Storage::disk('local')->exists($storedPath)) {
                Storage::disk('local')->delete($storedPath);
            }
            $file = $request->file('attachment');
            $storedPath = $file->storeAs(
                'hrm/leaves',
                Str::uuid().'.'.$file->extension(),
                'local'
            );
        }

        $start_date = new DateTime($request->start_date);
        $end_date   = new DateTime($request->end_date);
        // Fix: use ->days (total days) not ->d (days-remainder after months)
        $days_diff  = $start_date->diff($end_date)->days + 1;

        $leave_data = [
            'employee_id'   => $request->employee_id,
            'company_id'    => $request->company_id,
            'department_id' => $request->department_id,
            'leave_type_id' => $request->leave_type_id,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'days'          => $days_diff,
            'reason'        => $request->reason,
            'attachment'    => $storedPath,
            'half_day'      => (bool) $request->half_day,
            'status'        => $request->status,
        ];


        $defaultLeave = config('hrm.default_annual_leave', 21);
        $employee_leave_info = Employee::find($request->employee_id);
        if (! $employee_leave_info) {
            return response()->json(['error' => 'Employee not found', 'isvalid' => false], 404);
        }

        $empRemaining = intval($employee_leave_info->remaining_leave ?? $defaultLeave);

        // Restore balance if the previous leave was approved
        if ($leave->status === 'approved') {
            $restoredRemaining = $empRemaining + intval($leave->days);
            if ($days_diff > $restoredRemaining) {
                return response()->json(['remaining_leave' => 'Remaining leave balance is insufficient.', 'isvalid' => false]);
            }
            // Temporarily restore the old days so we can re-deduct below
            $empRemaining = $restoredRemaining;
            $employee_leave_info->remaining_leave = $empRemaining;
            $employee_leave_info->save();
            logger()->channel('stack')->info('hrm.leave.balance_restored', [
                'employee_id'    => $employee_leave_info->id,
                'leave_id'       => $leave->id,
                'restored_days'  => intval($leave->days),
                'balance_after'  => $empRemaining,
                'actor_id'       => auth()->id(),
            ]);
        } elseif ($days_diff > $empRemaining) {
            return response()->json(['remaining_leave' => 'Remaining leave balance is insufficient.', 'isvalid' => false]);
        }

        if ($request->status === 'approved') {
            $before = $empRemaining;
            $after  = max(0, $empRemaining - $days_diff);
            $employee_leave_info->remaining_leave = $after;
            $employee_leave_info->save();
            logger()->channel('stack')->info('hrm.leave.balance_deducted', [
                'employee_id'    => $employee_leave_info->id,
                'leave_id'       => $leave->id,
                'deducted_days'  => $days_diff,
                'balance_before' => $before,
                'balance_after'  => $after,
                'actor_id'       => auth()->id(),
            ]);
        }

        $leave->update($leave_data);

        $this->logHrmAudit('hrm.leave.updated', [
            'leave_id' => $leave->id,
            'employee_id' => $leave->employee_id,
            'company_id' => $leave->company_id,
            'department_id' => $leave->department_id,
            'status' => $leave->status,
            'start_date' => $leave->start_date,
            'end_date' => $leave->end_date,
            'days' => $leave->days,
        ], $leave);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true, 'isvalid' => true]);
        }
        return redirect()->route('hrm.leaves.index')->with('success', 'Leave updated successfully.');
    }




    //----------- Delete  Leave --------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'delete', Leave::class);

        $leave = Leave::findOrFail($id);
        $leaveSnapshot = [
            'leave_id' => $leave->id,
            'employee_id' => $leave->employee_id,
            'company_id' => $leave->company_id,
            'department_id' => $leave->department_id,
            'status' => $leave->status,
            'start_date' => $leave->start_date,
            'end_date' => $leave->end_date,
            'days' => $leave->days,
        ];
        $leave->deleted_at = Carbon::now();
        $leave->save();

        $this->logHrmAudit('hrm.leave.deleted', $leaveSnapshot, $leave);

        // Delete from private storage (new path format)
        if ($leave->attachment && Storage::disk('local')->exists($leave->attachment)) {
            Storage::disk('local')->delete($leave->attachment);
        }

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('hrm.leaves.index')->with('success', 'Leave deleted successfully.');
    }

    /**
     * Serve a leave attachment through an authenticated route.
     * Files are stored in storage/app/private/hrm/leaves/ (not web-accessible).
     */
    public function attachment(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (! $user || (! $user->can('hrm.access') && ! $user->can('hrm.leaves') && ! $user->can('leave.view'))) {
            abort(403);
        }

        $leave = Leave::whereNull('deleted_at')->findOrFail($id);

        if (! $leave->attachment) {
            abort(404);
        }

        $path = $leave->attachment;

        if (! Storage::disk('local')->exists($path)) {
            abort(404, 'Attachment not found.');
        }

        return Storage::disk('local')->response($path);
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

    $this->authorizeForUser($this->getAuthUser($request), 'delete', Leave::class);

        $selectedIds = $request->selectedIds;
        foreach ($selectedIds as $leave_id) {

            $leave = Leave::findOrFail($leave_id);
            $leave->deleted_at = Carbon::now();
            $leave->save();

            $attachment = $leave->attachment;

            $path = public_path() . '/images/leaves';
            $LeavePhoto = $path . '/' . $attachment;
            if (file_exists($LeavePhoto)) {
                if ($leave->attachment != 'no_image.png') {
                    @unlink($LeavePhoto);
                }
            }
        }

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('hrm.leaves.index')->with('success', 'Deleted successfully');
    }


}
