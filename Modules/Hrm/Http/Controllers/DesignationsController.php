<?php

namespace Modules\Hrm\Http\Controllers;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request; 
use App\Models\Designation;
use App\Models\Company;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class DesignationsController extends Controller
{

    protected function getAuthUser($request)
    {
        return $request->user('api') ?? $request->user() ?? auth()->user();
    }

    //----------- GET ALL  Designations --------------\\

    public function index(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.designations'))) {
            abort(403);
        }

        // Avoid errors if designations table is not present yet
        if (!Schema::hasTable('designations')) {
            if ($request->expectsJson()) {
                return response()->json(['designations' => [], 'totalRows' => 0]);
            }
            return view('hrm::designations.index', ['companies' => [], 'departments' => []]);
        }

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $data = array();

        if (!in_array(strtolower($dir ?? ''), ['asc', 'desc'])) {
            $dir = 'desc';
        }
        $allowed = ['id', 'designation', 'company_id', 'department_id', 'created_at'];
        if (empty($order) || !in_array($order, $allowed)) {
            $order = 'id';
        }

        $designations = Designation::with('department')->where('deleted_at', '=', null)

        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('designation', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $designations->count();
        if ($perPage == "-1") {
            $perPage = $totalRows;
        }

        if (is_numeric($perPage) && intval($perPage) > 0) {
            $designations = $designations->offset($offSet)
                ->limit(intval($perPage))
                ->orderBy($order, $dir)
                ->get();
        } else {
            $designations = $designations->orderBy($order, $dir)->get();
        }

        foreach ($designations as $designation) {

            $item['id'] = $designation->id;
            $item['designation'] = $designation->designation;
            $item['company_name'] = isset($designation['company']->name) ? $designation['company']->name : '';
            $item['company_id'] = isset($designation['company']->id) ? $designation['company']->id : null;
            $item['department_name'] = isset($designation['department']->department) ? $designation['department']->department : '';
            $item['department_id'] = isset($designation['department']->id) ? $designation['department']->id : null;
            $data[] = $item;
        }
        // Prepare a collection for the view
        $designations_for_view = collect($data);
    
        if ($request->expectsJson()) {
            return response()->json([
                'designations' => $data,
                'totalRows'   => $totalRows,
            ]);
        }

    $companies = Company::where('deleted_at', '=', null)->get(['id','name']);
    $departments = Department::where('deleted_at', '=', null)->get(['id','department']);
    return view('hrm::designations.index', compact('companies', 'departments', 'designations_for_view', 'totalRows', 'perPage', 'pageStart'));
    }

    public function create(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.designations'))) {
            abort(403);
        }

        $companies = Company::where('deleted_at', '=', null)->get(['id','name']);
        $departments = Department::where('deleted_at', '=', null)->get(['id','department']);

        if ($request->expectsJson()) {
            return response()->json([
                'companies' =>$companies,
            ]);
        }

        return view('hrm::designations.create', compact('companies', 'departments'));

    }

    //----------- Store new designation --------------\\

    public function store(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.designations'))) {
            abort(403);
        }

        request()->validate([
            'designation'   => 'required|string',
            'company_id'    => 'required',
            'department'    => 'required',
        ]);

        Designation::create([
            'designation'   => $request['designation'],
            'company_id'    => $request['company_id'],
            'department_id' => $request['department'],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

    return redirect()->route('hrm.designations.index')->with('success', 'Created successfully');
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
    }

    //------------ function edit -----------\\

    public function edit(Request $request , $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.designations'))) {
            abort(403);
        }

        $companies = Company::where('deleted_at', '=', null)->get(['id','name']);
        $departments = Department::where('deleted_at', '=', null)->get(['id','department']);

        if ($request->expectsJson()) {
            return response()->json([
                'companies' =>$companies,
            ]);
        }

        $designation = Designation::findOrFail($id);
        return view('hrm::designations.edit', compact('companies', 'departments', 'designation'));

    }

    //-----------Update designation --------------\\

    public function update(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.designations'))) {
            abort(403);
        }

        request()->validate([
            'designation'   => 'required|string',
            'company_id'   => 'required',
            'department'    => 'required',
        ]);

        Designation::whereId($id)->update([
            'designation'   => $request['designation'],
            'company_id'    => $request['company_id'],
            'department_id' => $request['department'],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

    return redirect()->route('hrm.designations.index')->with('success', 'Updated successfully');
    }

    //----------- Delete  designation --------------\\

    public function destroy(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.designations'))) {
            abort(403);
        }

        \DB::transaction(function () use ($id) {

            Designation::whereId($id)->update([
                'deleted_at' => Carbon::now(),
            ]);

        }, 10);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

    return redirect()->route('hrm.designations.index')->with('success', 'Deleted successfully');
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || !$user->can('hrm.access')) {
            abort(403);
        }

        $selectedIds = $request->selectedIds;
        foreach ($selectedIds as $designation_id) {
            Designation::whereId($designation_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function Get_designations_by_department(Request $request)
    {
        $designations = Designation::where('department_id' , $request->id)->where('deleted_at', '=', null)->get();

        return response()->json($designations);
    }

}
