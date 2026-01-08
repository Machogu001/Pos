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

class LeaveController extends Controller
{

    protected function getAuthUser($request)
    {
        // Prefer api user if present (for API calls), otherwise fall back to web user
        return $request->user('api') ?? $request->user() ?? auth()->user();
    }

    //----------- GET ALL Leaves --------------\\

    public function index(Request $request)
    {
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

        // Build base query defensively: only join if the related tables/columns exist
        $leaves = Leave::query();

        // employees (employee_id expected to exist)
        if (Schema::hasTable('employees') && Schema::hasColumn('leaves', 'employee_id')) {
            $leaves = $leaves->join('employees', 'employees.id', '=', 'leaves.employee_id')
                ->selectRaw('leaves.*, employees.username AS employee_name, employees.id AS employee_id');
        } else {
            $leaves = $leaves->select('leaves.*');
        }

        // leave types (optional)
        if (Schema::hasTable('leave_types') && Schema::hasColumn('leaves', 'leave_type_id')) {
            // leave_types table uses 'name' column in migration; prefer name but fall back to title if present
            $labelCol = Schema::hasColumn('leave_types', 'name') ? 'name' : (Schema::hasColumn('leave_types', 'title') ? 'title' : null);
            if ($labelCol) {
                $leaves = $leaves->leftJoin('leave_types', 'leave_types.id', '=', 'leaves.leave_type_id')
                    ->selectRaw("leave_types.{$labelCol} AS leave_type_title, leave_types.id AS leave_type_id");
            }
        }

        // companies (optional)
        if (Schema::hasTable('companies') && Schema::hasColumn('leaves', 'company_id')) {
            $leaves = $leaves->leftJoin('companies', 'companies.id', '=', 'leaves.company_id')
                ->selectRaw('companies.name AS company_name, companies.id AS company_id');
        }

        // departments (optional)
        if (Schema::hasTable('departments') && Schema::hasColumn('leaves', 'department_id')) {
            $leaves = $leaves->leftJoin('departments', 'departments.id', '=', 'leaves.department_id')
                ->selectRaw('departments.department AS department_name, departments.id AS department_id');
        }

        $leaves = $leaves->where('leaves.deleted_at', '=', null)
            // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    // try to search across joined tables if they exist; use raw where clauses that will be ignored if tables not joined
                    return $query->whereRaw("COALESCE(employees.username, '') LIKE ?", ["%{$request->search}%"])
                        ->orWhereRaw("COALESCE(leave_types.name, '') LIKE ?", ["%{$request->search}%"]) 
                        ->orWhereRaw("COALESCE(companies.name, '') LIKE ?", ["%{$request->search}%"]) 
                        ->orWhereRaw("COALESCE(departments.department, '') LIKE ?", ["%{$request->search}%"]);
                });
            });

        // Apply company filter if provided (AJAX/browser filters)
        if ($request->filled('company_id')) {
            // prefer explicit leaves.company_id column when present, otherwise try companies.id from joined table
            if (Schema::hasColumn('leaves', 'company_id')) {
                $leaves = $leaves->where('leaves.company_id', $request->get('company_id'));
            } else {
                $leaves = $leaves->where('companies.id', $request->get('company_id'));
            }
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

    public function store(Request $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'create', Leave::class);

        request()->validate([
            'employee_id'      => 'required|exists:employees,id',
            'company_id'       => 'required|exists:companies,id',
            'department_id'    => 'required|exists:departments,id',
            'leave_type_id'    => 'required|exists:leave_types,id',
            'start_date'       => 'required',
            'end_date'         => 'required|after_or_equal:start_date',
            'status'           => 'required',
            'attachment'      => 'nullable|image|mimes:jpeg,png,jpg,bmp,gif,svg|max:2048',
        ]);

        if ($request->hasFile('attachment')) {


            $image = $request->file('attachment');
            $filename = time().'.'.$image->extension();  
            $image->move(public_path('/images/leaves'), $filename);

        } else {
            $filename = 'no_image.png';
        }

        $start_date = new DateTime($request->start_date);
        $end_date = new DateTime($request->end_date);
        $day     = $start_date->diff($end_date);
        $days_diff    = $day->d +1;
        $leave_type = LeaveType::findOrFail($request['leave_type_id']);

        $leave_data= [];
        $leave_data['employee_id'] = $request['employee_id'];
        $leave_data['company_id'] = $request['company_id'];
        $leave_data['department_id'] = $request['department_id'];
        $leave_data['leave_type_id'] = $request['leave_type_id'];
        $leave_data['start_date'] = $request['start_date'];
        $leave_data['end_date'] = $request['end_date'];
        $leave_data['days'] = $days_diff;
        $leave_data['reason'] = $request['reason'];
        $leave_data['attachment'] = $filename;
        $leave_data['half_day'] = $request['half_day'];
        $leave_data['status'] = $request['status'];

        $employee_leave_info = Employee::find($request->employee_id);
        if (!$employee_leave_info) {
            return response()->json(['error' => 'Employee not found', 'isvalid' => false], 404);
        }

        // Use configured default if remaining_leave is null
        $defaultLeave = config('hrm.default_annual_leave', 21);
        $empRemaining = intval($employee_leave_info->remaining_leave ?? $defaultLeave);

        if ($days_diff > $empRemaining) {
            return response()->json(['remaining_leave' => "remaining leaves are insufficient", 'isvalid' => false]);
        } elseif ($request->status == 'approved') {
            $before = $empRemaining;
            $after = max(0, $empRemaining - $days_diff);
            $employee_leave_info->remaining_leave = $after;
            $employee_leave_info->update();
            // Audit log
            logger()->info('leave_approved:remaining_changed', [
                'employee_id' => $employee_leave_info->id,
                'leave_employee_id' => $request->employee_id,
                'leave_days' => $days_diff,
                'before' => $before,
                'after' => $after,
                'action' => 'approve_create',
                'user_id' => auth()->id(),
            ]);
        }

        // Persist only columns that actually exist on the leaves table to avoid SQL errors
        $filtered_leave_data = [];
        foreach ($leave_data as $k => $v) {
            if (Schema::hasColumn('leaves', $k)) {
                $filtered_leave_data[$k] = $v;
            }
        }

        Leave::create($filtered_leave_data);
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true ,'isvalid' => true]);
        }
        return redirect()->route('hrm.leaves.index')->with('success', 'Created successfully');
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

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'update', Leave::class);

        request()->validate([
            'company_id'       => 'required|exists:companies,id',
            'department_id'    => 'required|exists:departments,id',
            'employee_id'      => 'required|exists:employees,id',
            'leave_type_id'    => 'required|exists:leave_types,id',
            'start_date'       => 'required',
            'end_date'         => 'required',
            'status'           => 'required',
            'attachment'      => 'nullable|image|mimes:jpeg,png,jpg,bmp,gif,svg|max:2048',
        ]);

        $leave = Leave::findOrFail($id);
        $CurrentAttachement = $leave->attachment;
        if ($request->attachment != null) {
            if ($request->attachment != $CurrentAttachement) {

                $image = $request->file('attachment');
                $filename = time().'.'.$image->extension();  
                $image->move(public_path('/images/leaves'), $filename);
                $path = public_path() . '/images/leaves';
                $LeavePhoto = $path . '/' . $CurrentAttachement;
                if (file_exists($LeavePhoto)) {
                    if ($leave->attachment != 'no_image.png') {
                        @unlink($LeavePhoto);
                    }
                }
            } else {
                $filename = $CurrentAttachement;
            }
        }else{
            $filename = $CurrentAttachement;
        }

        $start_date = new DateTime($request->start_date);
        $end_date = new DateTime($request->end_date);
        $day     = $start_date->diff($end_date);
        $days_diff    = $day->d +1;
        $leave_type = LeaveType::findOrFail($request['leave_type_id']);

        $leave_data= [];
        $leave_data['employee_id'] = $request['employee_id'];
        $leave_data['company_id'] = $request['company_id'];
        $leave_data['department_id'] = $request['department_id'];
        $leave_data['leave_type_id'] = $request['leave_type_id'];
        $leave_data['start_date'] = $request['start_date'];
        $leave_data['end_date'] = $request['end_date'];
        $leave_data['days'] = $days_diff;
        $leave_data['reason'] = $request['reason'];
        $leave_data['attachment'] = $filename;
        $leave_data['half_day'] = $request['half_day'];
        $leave_data['status'] = $request['status'];


        // return the old remaining_leave
        if ($leave->status == 'approved') {
            $employee_leave_info = Employee::find($request->employee_id);
            if (!$employee_leave_info) {
                return response()->json(['error' => 'Employee not found', 'isvalid' => false], 404);
            }

            $defaultLeave = config('hrm.default_annual_leave', 21);
            $empRemaining = intval($employee_leave_info->remaining_leave ?? $defaultLeave);

            if ($days_diff > ($empRemaining + intval($leave->days))) {
                return response()->json(['remaining_leave' => "remaining leaves are insufficient", 'isvalid' => false]);
            } else {
                $before = $empRemaining;
                $after = $empRemaining + intval($leave->days);
                $employee_leave_info->remaining_leave = $after;
                $employee_leave_info->update();
                logger()->info('leave_update:remaining_restored', [
                    'employee_id' => $employee_leave_info->id,
                    'leave_id' => $leave->id,
                    'restored_days' => intval($leave->days),
                    'before' => $before,
                    'after' => $after,
                    'action' => 'restore_old_approved',
                    'user_id' => auth()->id(),
                ]);
            }
        }

        if ($leave->status != 'approved') {
            $employee_leave_info = Employee::find($request->employee_id);
            if (!$employee_leave_info) {
                return response()->json(['error' => 'Employee not found', 'isvalid' => false], 404);
            }
            $defaultLeave = config('hrm.default_annual_leave', 21);
            $empRemaining = intval($employee_leave_info->remaining_leave ?? $defaultLeave);
            if ($days_diff > $empRemaining) {
                return response()->json(['remaining_leave' => "remaining leaves are insufficient", 'isvalid' => false]);
            }
        }

        if ($request->status == 'approved') {
            $employee_leave_info = Employee::find($request->employee_id);
            if (!$employee_leave_info) {
                return response()->json(['error' => 'Employee not found', 'isvalid' => false], 404);
            }
            $defaultLeave = config('hrm.default_annual_leave', 21);
            $empRemaining = intval($employee_leave_info->remaining_leave ?? $defaultLeave);
            $before = $empRemaining;
            $after = max(0, $empRemaining - $days_diff);
            $employee_leave_info->remaining_leave = $after;
            $employee_leave_info->update();
            logger()->info('leave_update:remaining_deducted', [
                'employee_id' => $employee_leave_info->id,
                'leave_id' => $leave->id,
                'deducted_days' => $days_diff,
                'before' => $before,
                'after' => $after,
                'action' => 'approve_update',
                'user_id' => auth()->id(),
            ]);
        }

    
        // Filter out any keys that are not actual columns on the leaves table
        $filtered_leave_data = [];
        foreach ($leave_data as $k => $v) {
            if (Schema::hasColumn('leaves', $k)) {
                $filtered_leave_data[$k] = $v;
            }
        }

        Leave::find($id)->update($filtered_leave_data);
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true ,'isvalid' => true]);
        }
        return redirect()->route('hrm.leaves.index')->with('success', 'Updated successfully');
    }




    //----------- Delete  Leave --------------\\

    public function destroy(Request $request, $id)
    {
    $this->authorizeForUser($this->getAuthUser($request), 'delete', Leave::class);

        $leave = Leave::findOrFail($id);
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
      
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('hrm.leaves.index')->with('success', 'Deleted successfully');
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
