<?php

namespace Modules\Hrm\Http\Controllers;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Schema;

class DepartmentsController extends Controller
{

    protected function getAuthUser($request)
    {
        return $request->user('api') ?? $request->user() ?? auth()->user();
    }

    //----------- GET ALL  Department --------------\\

    public function index(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.departments'))) {
            abort(403);
        }

        // If departments table does not exist yet, avoid SQL errors
        if (!Schema::hasTable('departments')) {
            if ($request->expectsJson()) {
                return response()->json(['departments' => [], 'totalRows' => 0]);
            }
            return view('hrm::departments.index', ['companies' => [], 'employees' => []]);
        }

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number; only computed when perPage is numeric
        $offSet = 0;
        if (is_numeric($perPage) && intval($perPage) > 0) {
            $offSet = ($pageStart * intval($perPage)) - intval($perPage);
        }
        $order = $request->SortField;
        $dir = $request->SortType;

        // sanitize sort direction and field
        if (!in_array(strtolower($dir ?? ''), ['asc', 'desc'])) {
            $dir = 'desc';
        }
        $allowed = ['id', 'department', 'company_id', 'created_at', 'updated_at'];
        if (empty($order) || !in_array($order, $allowed)) {
            $order = 'id';
        }

        // Build base query defensively: only join if the related tables/columns exist
        $departments = Department::query();
        if (Schema::hasColumn('departments', 'department_head') && Schema::hasTable('employees')) {
            $departments = $departments->leftJoin('employees','employees.id','=','departments.department_head')
                ->selectRaw('departments.*, employees.username AS employee_head');
        } else {
            $departments = $departments->select('departments.*');
        }
        if (Schema::hasTable('companies') && Schema::hasColumn('departments','company_id')) {
            $departments = $departments->join('companies','companies.id','=','departments.company_id')
                ->selectRaw((Schema::hasColumn('departments','department_head') ? '' : '') . ' companies.name AS company_name');
        }
        $departments = $departments->where('departments.deleted_at' , '=', null)

        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('departments.department', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $departments->count();
        if ($perPage == "-1") {
            $perPage = $totalRows;
        }

        if (is_numeric($perPage) && intval($perPage) > 0) {
            $departments = $departments->offset($offSet)
                ->limit(intval($perPage))
                ->orderBy($order, $dir)
                ->get();
        } else {
            $departments = $departments->orderBy($order, $dir)->get();
        }


        if ($request->expectsJson()) {
            return response()->json([
                'departments' => $departments,
                'totalRows'   => $totalRows,
            ]);
        }

    $companies = Company::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']);
    // include firstname/lastname for labels if username is missing
    $employees = Employee::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','username','firstname','lastname']);

    // Pass the departments collection to the view so the list can be rendered
    return view('hrm::departments.index', compact('companies', 'employees', 'departments', 'totalRows', 'perPage', 'pageStart'));
    }

    public function create(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.departments'))) {
            abort(403);
        }

    $companies = Company::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']);
    $employees = Employee::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','username','firstname','lastname']);

        if ($request->expectsJson()) {
            return response()->json([
                'companies' =>$companies,
            ]);
        }

        return view('hrm::departments.create', compact('companies', 'employees'));

    }

    //----------- Store new department --------------\\

    public function store(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.departments'))) {
            abort(403);
        }

        request()->validate([
            'department'   => 'required|string',
            'company_id'   => 'required',
        ]);

        Department::create([
            'department'        => $request['department'],
            'company_id'        => $request['company_id'],
            'department_head'   => $request['department_head']?$request['department_head']:Null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

    return redirect()->route('hrm.departments.index')->with('success', 'Created successfully');
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
    }

    //------------ function edit -----------\\

    public function edit(Request $request , $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.departments'))) {
            abort(403);
        }

    $companies = Company::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']);
    $employees = Employee::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','username','firstname','lastname']);

        if ($request->expectsJson()) {
            return response()->json([
                'companies' =>$companies,
            ]);
        }

        $department = Department::findOrFail($id);
        return view('hrm::departments.edit', compact('companies', 'employees', 'department'));

    }

    //-----------Update department --------------\\

    public function update(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.departments'))) {
            abort(403);
        }

        request()->validate([
            'department'   => 'required|string',
            'company_id'   => 'required',
        ]);

        Department::whereId($id)->update([
            'department'        => $request['department'],
            'company_id'        => $request['company_id'],
            'department_head'   => $request['department_head']?$request['department_head']:Null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

    return redirect()->route('hrm.departments.index')->with('success', 'Updated successfully');
    }

    //----------- Set department head (AJAX or form) --------------\
    public function setHead(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.departments'))) {
            abort(403);
        }

        $request->validate([
            'department_head' => 'nullable|exists:employees,id'
        ]);

        $headId = $request->input('department_head') ?: null;

        Department::whereId($id)->update([
            'department_head' => $headId,
        ]);

        if ($request->expectsJson()) {
            $employee = $headId ? Employee::find($headId) : null;
            $label = $employee ? ($employee->username ?? trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? ''))) : null;
            return response()->json(['success' => true, 'department_head' => $headId, 'employee_name' => $label]);
        }

        return redirect()->route('hrm.departments.index')->with('success', 'Updated successfully');
    }

    //----------- Remove department head --------------\
    public function removeHead(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.departments'))) {
            abort(403);
        }

        Department::whereId($id)->update(['department_head' => null]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('hrm.departments.index')->with('success', 'Updated successfully');
    }

    //----------- Delete  department --------------\\

    public function destroy(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || !$user->can('hrm.access')) {
            abort(403);
        }

        \DB::transaction(function () use ($id) {

            Department::whereId($id)->update([
                'deleted_at' => Carbon::now(),
            ]);

        }, 10);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

    return redirect()->route('hrm.departments.index')->with('success', 'Deleted successfully');
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || !$user->can('hrm.access')) {
            abort(403);
        }

        $selectedIds = $request->selectedIds;
        foreach ($selectedIds as $department_id) {
            Department::whereId($department_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function Get_all_Departments()
    {
        $departments = Department::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','department']);

        return response()->json($departments);
    }

}
