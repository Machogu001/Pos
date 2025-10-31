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
        $this->authorizeForUser($this->getAuthUser($request), 'view', Department::class);

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
    $employees = Employee::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','username']);

    // Pass the departments collection to the view so the list can be rendered
    return view('hrm::departments.index', compact('companies', 'employees', 'departments', 'totalRows', 'perPage', 'pageStart'));
    }

    public function create(Request $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'create', Department::class);

        $companies = Company::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']);
        $employees = Employee::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','username']);

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
        $this->authorizeForUser($this->getAuthUser($request), 'create', Department::class);

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

    return redirect()->route('hrm.departments.index')->with('success', 'Department created');
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
    }

    //------------ function edit -----------\\

    public function edit(Request $request , $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'update', Department::class);

        $companies = Company::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']);
        $employees = Employee::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','username']);

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
        $this->authorizeForUser($this->getAuthUser($request), 'update', Department::class);

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

    return redirect()->route('hrm.departments.index')->with('success', 'Department updated');
    }

    //----------- Delete  department --------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'delete', Department::class);

        \DB::transaction(function () use ($id) {

            Department::whereId($id)->update([
                'deleted_at' => Carbon::now(),
            ]);

        }, 10);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

    return redirect()->route('hrm.departments.index')->with('success', 'Department deleted');
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'delete', Department::class);

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
