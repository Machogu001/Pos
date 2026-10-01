<?php

namespace Modules\Hrm\Http\Controllers;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request; 
use App\Models\Designation;
use App\Models\Company;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DesignationsController extends Controller
{

    protected function businessId()
    {
        return session('business.id');
    }

    protected function applyBusinessScope($query, string $table = 'designations')
    {
        $businessId = $this->businessId();

        if (! $businessId || ! Schema::hasColumn($table, 'business_id')) {
            return $query;
        }

        return $query->where(function ($tenantQuery) use ($businessId, $table) {
            $tenantQuery->where($table . '.business_id', $businessId)
                ->orWhereNull($table . '.business_id');
        });
    }

    protected function designationPayload(Request $request): array
    {
        $payload = [
            'designation' => $request->input('designation'),
            'company_id' => $request->input('company_id'),
            'department_id' => $request->input('department'),
        ];

        if (Schema::hasColumn('designations', 'business_id')) {
            $payload['business_id'] = $this->businessId();
        }

        return $payload;
    }

    protected function normalizeDesignationName(string $designation): string
    {
        return strtolower(trim($designation));
    }

    protected function findDuplicateDesignation(string $designation, $companyId, $departmentId, ?int $ignoreId = null)
    {
        $query = $this->applyBusinessScope(Designation::query(), 'designations')
            ->whereNull('deleted_at')
            ->where('company_id', $companyId)
            ->where('department_id', $departmentId)
            ->whereRaw('LOWER(TRIM(designation)) = ?', [$this->normalizeDesignationName($designation)]);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->first();
    }

    protected function companies()
    {
        $columns = ['id', 'name'];
        if (Schema::hasColumn('companies', 'business_id')) {
            $columns[] = 'business_id';
        }

        return $this->applyBusinessScope(Company::query(), 'companies')
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get($columns);
    }

    protected function departments()
    {
        return $this->applyBusinessScope(Department::query(), 'departments')
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get(['id', 'department', 'company_id']);
    }

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

        $designations = $this->applyBusinessScope(Designation::with(['company:id,name', 'department:id,department,company_id', 'department.company:id,name']), 'designations')
            ->where('deleted_at', '=', null)

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
            $effectiveCompany = $designation->company ?: optional($designation->department)->company;
            $effectiveCompanyId = $effectiveCompany->id ?? $designation->company_id ?? optional($designation->department)->company_id;
            $signature = implode('|', [
                $this->normalizeDesignationName($designation->designation),
                $effectiveCompanyId ?: 'no-company',
                $designation->department_id ?: 'no-department',
            ]);

            $item['id'] = $designation->id;
            $item['designation'] = $designation->designation;
            $item['company_name'] = $effectiveCompany->name ?? '';
            $item['company_id'] = $effectiveCompanyId ?: null;
            $item['department_name'] = isset($designation['department']->department) ? $designation['department']->department : '';
            $item['department_id'] = isset($designation['department']->id) ? $designation['department']->id : null;

            if (! isset($data[$signature]) || (empty($data[$signature]['company_id']) && ! empty($item['company_id']))) {
                $data[$signature] = $item;
            }
        }
        // Prepare a collection for the view
        $designations_for_view = collect(array_values($data));
        $totalRows = $designations_for_view->count();
    
        if ($request->expectsJson()) {
            return response()->json([
                'designations' => $data,
                'totalRows'   => $totalRows,
            ]);
        }

    $companies = $this->companies();
    $departments = $this->departments();
    return view('hrm::designations.index', compact('companies', 'departments', 'designations_for_view', 'totalRows', 'perPage', 'pageStart'));
    }

    public function create(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.designations'))) {
            abort(403);
        }

        $companies = $this->companies();
        $departments = $this->departments();

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

        if ($this->findDuplicateDesignation($request->input('designation'), $request->input('company_id'), $request->input('department'))) {
            throw ValidationException::withMessages([
                'designation' => 'This designation already exists for the selected company and department.',
            ]);
        }

        Designation::create($this->designationPayload($request));

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

        $companies = $this->companies();
        $departments = $this->departments();

        if ($request->expectsJson()) {
            return response()->json([
                'companies' =>$companies,
            ]);
        }

        $designation = $this->applyBusinessScope(Designation::query(), 'designations')->findOrFail($id);
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

        if ($this->findDuplicateDesignation($request->input('designation'), $request->input('company_id'), $request->input('department'), (int) $id)) {
            throw ValidationException::withMessages([
                'designation' => 'This designation already exists for the selected company and department.',
            ]);
        }

        $this->applyBusinessScope(Designation::query(), 'designations')
            ->whereId($id)
            ->update($this->designationPayload($request));

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

            $this->applyBusinessScope(Designation::query(), 'designations')->whereId($id)->update([
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
            $this->applyBusinessScope(Designation::query(), 'designations')->whereId($designation_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function Get_designations_by_department(Request $request)
    {
        $designations = $this->applyBusinessScope(Designation::query(), 'designations')
            ->where('department_id', $request->id)
            ->where('deleted_at', '=', null)
            ->get();

        return response()->json($designations);
    }

}
