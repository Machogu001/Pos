<?php

namespace Modules\Hrm\Http\Controllers;
use App\Http\Controllers\Controller;

use App\Models\Employee;
use App\Models\Company;
use App\Models\Designation;
use App\Models\EmployeeExperience;
use App\Models\EmployeeDocument;
use App\Models\EmployeeAccount;
use App\Models\Department;
use App\Models\OfficeShift;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Award;
use App\Models\Travel;
use App\Models\Complaint;
use App\Models\Project;
use App\Models\Task;
use App\Models\Training;
use App\utils\helpers;
use App\Business;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Intervention\Image\ImageManagerStatic as Image;

class EmployeesController extends Controller
{

    /**
     * Resolve the authenticated user for authorization checks.
     * Prefer the 'api' guard user when available, otherwise fall back to the
     * default session/authenticated user.
     */
    protected function getAuthUser($request)
    {
        return $request->user('api') ?? $request->user() ?? auth()->user();
    }

    //------------ GET ALL employees -----------\\

    public function index(Request $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'view', Employee::class);
        // How many items do you want to display.
        $perPageRaw = $request->limit ?? 10;
        if ($perPageRaw == "-1") {
            // special case: return all
            $perPage = -1;
        } else {
            $perPage = max(1, (int) $perPageRaw);
        }

        $pageStart = max(1, (int) \Request::get('page', 1));
        // Start displaying items from this number;
        $offSet = ($pageStart * ($perPage === -1 ? 0 : $perPage)) - ($perPage === -1 ? 0 : $perPage);
        // Normalize and validate ordering inputs
        $order = $request->SortField ?? 'id';
        $dir = strtolower($request->SortType ?? 'desc');

        // Allowed columns to prevent SQL injection / invalid column errors
        $allowedOrders = [
            'id', 'firstname', 'lastname', 'username', 'company_id', 'department_id', 'designation_id', 'joining_date'
        ];
        if (! in_array($order, $allowedOrders)) {
            $order = 'id';
        }

        if (! in_array($dir, ['asc', 'desc'])) {
            $dir = 'desc';
        }
        $helpers = new helpers();
        // Filter fields With Params to retrieve
        $param = array(
            0 => 'like',
            1 => 'like',
            2 => '=',
           
        );
        $columns = array(
            0 => 'username',
            1 => 'employment_type',
            2 => 'company_id',
        );
        $data = array();

        // Only eager load relations if their tables exist to avoid SQL errors
        $with = [];
        if (Schema::hasTable('companies')) {
            $with[] = 'company:id,name';
        }
        if (Schema::hasTable('office_shifts')) {
            $with[] = 'office_shift:id,name';
        }
        if (Schema::hasTable('departments')) {
            $with[] = 'department:id,department';
        }
        if (Schema::hasTable('designations')) {
            $with[] = 'designation:id,designation';
        }

        $employees = Employee::when(!empty($with), function($q) use ($with) {
                return $q->with($with);
            })
            ->where('deleted_at', '=', null)
            ->where('leaving_date' , NULL);

         //Multiple Filter
        $Filtred = $helpers->filter($employees, $columns, $param, $request)
        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('firstname', 'LIKE', "%{$request->search}%")
                        ->orWhere('lastname', 'LIKE', "%{$request->search}%")
                        ->orWhere('username', 'LIKE', "%{$request->search}%");
                });
            });
        // Count using the filtered query
        $totalRows = $Filtred->count();

        // If caller expects JSON (AJAX/API) we return flattened JSON (existing behavior)
        if ($request->wantsJson() || $request->expectsJson()) {
            if ($perPage === -1) {
                $perPage = $totalRows > 0 ? $totalRows : 1;
                $items = $Filtred->orderBy($order, $dir)->get();
            } else {
                $items = $Filtred->orderBy($order, $dir)->offset($offSet)->limit($perPage)->get();
            }

            $data = [];
            foreach ($items as $employee) {
                $item = [];
                $item['id'] = $employee->id;
                $item['firstname'] = $employee->firstname;
                $item['lastname'] = $employee->lastname;
                $item['phone'] = $employee->phone;
                $item['department_name'] = optional($employee->department)->department;
                $item['designation_name'] = optional($employee->designation)->designation;
                $item['office_shift_name'] = optional($employee->office_shift)->name;
                $data[] = $item;
            }

            return response()->json([
                'employees' => $data,
                'companies' => Schema::hasTable('business') ? Business::orderBy('id', 'desc')->get(['id', 'name']) : $companies,
                'totalRows' => $totalRows,
            ]);
        }

        // For regular browser requests, use LengthAwarePaginator so Blade can render pagination links
        $perPageForPaginator = ($perPage === -1) ? ($totalRows > 0 ? $totalRows : 1) : $perPage;
    $pageStart = max(1, (int) request()->get('page', 1));

        $paginator = $Filtred->orderBy($order, $dir)->paginate($perPageForPaginator, ['*'], 'page', $pageStart);

        // Map items into a simple array for the view while keeping the paginator for links
        $data = [];
        foreach ($paginator->items() as $employee) {
            $item = [];
            $item['id'] = $employee->id;
            $item['firstname'] = $employee->firstname;
            $item['lastname'] = $employee->lastname;
            $item['phone'] = $employee->phone;
            $item['department_name'] = optional($employee->department)->department;
            $item['designation_name'] = optional($employee->designation)->designation;
            $item['office_shift_name'] = optional($employee->office_shift)->name;
            $data[] = $item;
        }

    // Choose businesses (project-level) if available, otherwise companies
    if (Schema::hasTable('business')) {
        $companies = Business::orderBy('id', 'desc')->get(['id', 'name']);
    } else {
        $companies = Schema::hasTable('companies') ? Company::where('deleted_at', '=', null)->get(['id', 'name']) : collect([]);
    }

        // Render the blade view for browsers. Pass the paginator-derived data and the paginator itself.
        return view('hrm::employees.index', [
            'employees' => $data,
            'companies' => $companies,
            'totalRows' => $totalRows,
            'paginator' => $paginator,
        ]);

    }

      //---------------- Show Form Create Employee ---------------\\

      public function create(Request $request)
      {

          $this->authorizeForUser($this->getAuthUser($request), 'create', Employee::class);

          if (Schema::hasTable('business')) {
              $companies = Business::orderBy('id', 'desc')->get(['id','name']);
          } else {
              $companies = Schema::hasTable('companies') ? Company::where('deleted_at', '=', null)->get(['id','name']) : collect([]);
          }
          $departments = Schema::hasTable('departments') ? Department::where('deleted_at', '=', null)->get(['id','department']) : collect([]);
          $designations = Schema::hasTable('designations') ? Designation::where('deleted_at', '=', null)->get(['id','designation']) : collect([]);
          $office_shifts = Schema::hasTable('office_shifts') ? OfficeShift::where('deleted_at', '=', null)->get(['id','name']) : collect([]);

          // If the browser requested HTML, return a blade form. Otherwise return JSON (API clients).
          if (! $request->wantsJson()) {
              return view('hrm::employees.create', compact('companies', 'departments', 'designations', 'office_shifts'));
          }

          return response()->json([
              'companies' => $companies,
              'departments' => $departments,
              'designations' => $designations,
              'office_shifts' => $office_shifts,
          ]);
      }


  //----------------Store  Employee ---------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'create', Employee::class);

            $this->validate($request, [
                'firstname'      => 'required|string',
                'lastname'       => 'required|string',
                'gender'         => 'required',
                'company_id'     => 'required',
                // department, designation and office_shift are optional in the form ("--"),
                // allow nulls so the record can be created and updated later via edit.
                'department_id'  => 'nullable',
                'designation_id' => 'nullable',
                'office_shift_id'=> 'nullable',
            ]);
          
            $data = [];
            $data['firstname'] = $request['firstname'];
            $data['lastname'] = $request['lastname'];
            $data['username'] = $request['firstname'] .' '.$request['lastname'];
            $data['country'] = $request['country'];
            $data['email'] = $request['email'];
            $data['gender'] = $request['gender'];
            $data['phone'] = $request['phone'];
            $data['birth_date'] = $request['birth_date'];
            $data['company_id'] = $request['company_id'];
            $data['department_id'] = $request['department_id'];
            $data['designation_id'] = $request['designation_id'];
            $data['office_shift_id'] = $request['office_shift_id'];
            $data['joining_date'] = $request['joining_date'];
            
            Employee::create($data);
            
            return response()->json(['success' => true]);
    }

   
     //------------ function show -----------\\

      public function show(Request $request, $id)
      {
        $this->authorizeForUser($this->getAuthUser($request), 'view', Employee::class);

        $employee = Employee::where('deleted_at', '=', null)->findOrFail($id);
        if (Schema::hasTable('business')) {
            $companies = Business::orderBy('id', 'desc')->get(['id','name']);
        } else {
            $companies = Company::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']);
        }
        $office_shifts = Schema::hasTable('office_shifts') ? OfficeShift::where('company_id' , $employee->company_id)->where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']) : collect([]);
        $departments = Schema::hasTable('departments') ? Department::where('company_id' , $employee->company_id)->where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','department']) : collect([]);
        $designations = Schema::hasTable('designations') ? Designation::where('department_id' , $employee->department_id)->where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','designation']) : collect([]);

        if (! $request->wantsJson()) {
            $deductions = collect([]);
            if (Schema::hasTable('employee_deductions')) {
                $deductions = \DB::table('employee_deductions')->where('employee_id', $employee->id)->orderBy('created_at', 'desc')->get();
            }
            return view('hrm::employees.show', compact('employee', 'companies', 'office_shifts', 'departments', 'designations', 'deductions'));
        }

        return response()->json([
            'employee' => $employee,
            'companies' => $companies,
            'office_shifts' => $office_shifts,
            'departments' => $departments,
            'designations' => $designations,
        ]);
    
    }

    public function edit(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'update', Employee::class);

        $employee = Employee::where('deleted_at', '=', null)->findOrFail($id);
        if (Schema::hasTable('business')) {
            $companies = Business::orderBy('id', 'desc')->get(['id','name']);
        } else {
            $companies = Company::where('deleted_at', '=', null)->get(['id','name']);
        }
    $office_shifts = Schema::hasTable('office_shifts') ? OfficeShift::where('company_id' , $employee->company_id)->where('deleted_at', '=', null)->get(['id','name']) : collect([]);
    $departments = Schema::hasTable('departments') ? Department::where('company_id' , $employee->company_id)->where('deleted_at', '=', null)->get(['id','department']) : collect([]);
    $designations = Schema::hasTable('designations') ? Designation::where('department_id' , $employee->department_id)->where('deleted_at', '=', null)->get(['id','designation']) : collect([]);
        
        return response()->json([
            'employee' => $employee,
            'companies' => $companies,
            'office_shifts' => $office_shifts,
            'departments' => $departments,
            'designations' => $designations,
        ]);     
    }

    // Suspend or unsuspend payroll for an employee
    public function suspend(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'update', Employee::class);

        $employee = Employee::findOrFail($id);
        $action = $request->input('action', 'suspend');
        if ($action === 'unsuspend') {
            $employee->suspended = false;
        } else {
            $employee->suspended = true;
        }
        $employee->save();

        return response()->json(['success' => true, 'suspended' => (bool) $employee->suspended]);
    }

    // Deductions: list for employee
    public function deductionsIndex(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'view', Employee::class);
        if (! Schema::hasTable('employee_deductions')) {
            return response()->json(['deductions' => []]);
        }
        $deductions = \DB::table('employee_deductions')->where('employee_id', $id)->orderBy('created_at', 'desc')->get();
        return response()->json(['deductions' => $deductions]);
    }

    // Deductions: store
    public function deductionsStore(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'update', Employee::class);
        $this->validate($request, [
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string',
            'type' => 'nullable|string',
        ]);

        \DB::table('employee_deductions')->insert([
            'employee_id' => $id,
            'amount' => $request->input('amount'),
            'type' => $request->input('type'),
            'reason' => $request->input('reason'),
            'created_by' => auth()->id() ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    // Deductions: delete
    public function deductionsDestroy(Request $request, $employee_id, $deduction_id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'update', Employee::class);
        if (! Schema::hasTable('employee_deductions')) {
            return response()->json(['success' => false], 404);
        }
        \DB::table('employee_deductions')->where('id', $deduction_id)->where('employee_id', $employee_id)->delete();
        return response()->json(['success' => true]);
    }

     //---------------- UPDATE Employee -------------\\

     public function update(Request $request, $id)
     {

         $this->authorizeForUser($this->getAuthUser($request), 'update', Employee::class);
 
         $this->validate($request, [
            'firstname'      => 'required|string',
            'lastname'       => 'required|string',
            'country'        => 'required|string',
            'gender'         => 'required',
            'phone'          => 'required',
            'total_leave'    => 'required|numeric|min:0',
            'company_id'     => 'required',
            'department_id'  => 'nullable',
            'designation_id' => 'nullable',
            'office_shift_id'=> 'nullable',
            'basic_salary'   => 'nullable|numeric',
            'hourly_rate'     => 'nullable|numeric',
        ]);

       
        $data = [];
        $data['firstname'] = $request['firstname'];
        $data['lastname'] = $request['lastname'];
        $data['username'] = $request['firstname'] .' '.$request['lastname'];
        $data['country'] = $request['country'];
        $data['email'] = $request['email'];
        $data['gender'] = $request['gender'];
        $data['phone'] = $request['phone'];
        $data['birth_date'] = $request['birth_date'];
        $data['company_id'] = $request['company_id'];
        $data['department_id'] = $request['department_id'];
        $data['designation_id'] = $request['designation_id'];
        $data['office_shift_id'] = $request['office_shift_id'];
        $data['joining_date'] = $request['joining_date'];
        $data['role_users_id'] = $request['role_users_id'];
        $data['leaving_date'] = $request['leaving_date']?$request['leaving_date']:NULL;
        $data['marital_status'] = $request['marital_status'];
        $data['employment_type'] = $request['employment_type'];
        $data['city'] = $request['city'];
        $data['province'] = $request['province'];
        $data['zipcode'] = $request['zipcode'];
        $data['address'] = $request['address'];
        $data['basic_salary'] = $request['basic_salary'];
        $data['hourly_rate'] = $request['hourly_rate'];

        //calculation of total_leave & remaining_leave
        $employee_leave_info = Employee::find($id);
        if($employee_leave_info->total_leave == 0)
        {
            $data['total_leave'] = $request->total_leave;
            $data['remaining_leave'] = $request->total_leave;
        }
        elseif($request->total_leave > $employee_leave_info->total_leave ){
            $data['total_leave'] = $request->total_leave;
            $data['remaining_leave'] = $request->remaining_leave + ($request->total_leave - $employee_leave_info->total_leave);
        }
         elseif($request->total_leave < $employee_leave_info->total_leave ){
            $data['total_leave'] = $request->total_leave;
            $data['remaining_leave'] = $request->remaining_leave - ($employee_leave_info->total_leave - $request->total_leave);

        }else{
            $data['total_leave'] = $request->total_leave;
            $data['remaining_leave'] = $employee_leave_info->remaining_leave;
        }
        
        Employee::find($id)->update($data);

         return response()->json(['success' => true]);
     }

    //------------ Delete Employee -----------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'delete', Employee::class);

        Employee::whereId($id)->update([
            'deleted_at' => Carbon::now(),
        ]);
        return response()->json(['success' => true]);
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

        $this->authorizeForUser($this->getAuthUser($request), 'delete', Employee::class);

        $selectedIds = $request->selectedIds;
        foreach ($selectedIds as $employee_id) {
            Employee::whereId($employee_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }
        return response()->json(['success' => true]);

    }

    //--------------- Trash (deleted employees) view ---------------\
    public function trash(Request $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'view', Employee::class);

        $perPage = $request->limit ?? 10;
        $page = max(1, (int) $request->get('page', 1));

        $query = Employee::whereNotNull('deleted_at')->orderBy('deleted_at', 'desc');
        $total = $query->count();
        $perPage = ($perPage == '-1') ? ($total > 0 ? $total : 1) : max(1, (int)$perPage);
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = [];
        foreach ($paginator->items() as $e) {
            $items[] = [
                'id' => $e->id,
                'firstname' => $e->firstname,
                'lastname' => $e->lastname,
                'deleted_at' => $e->deleted_at,
            ];
        }

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['employees' => $items, 'totalRows' => $total]);
        }

        return view('hrm::employees.trash', ['employees' => $items, 'paginator' => $paginator, 'totalRows' => $total]);
    }

    //--------------- Restore deleted employee ---------------\
    public function restore(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'update', Employee::class);
        $emp = Employee::whereId($id)->first();
        if (! $emp) {
            return response()->json(['success' => false], 404);
        }
        $emp->deleted_at = null;
        $emp->save();
        return response()->json(['success' => true]);
    }

    //--------------- Permanently delete employee ---------------\
    public function forceDelete(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'delete', Employee::class);
        $emp = Employee::whereId($id)->first();
        if (! $emp) {
            return response()->json(['success' => false], 404);
        }
        $emp->delete();
        return response()->json(['success' => true]);
    }


    public function Get_employees_by_department(Request $request)
    {
        if (! Schema::hasTable('employees')) {
            return response()->json(collect([]));
        }

        $employees = Employee::where('department_id' , $request->id)->where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','username']);

        return response()->json($employees);
    }

    // Get employees by company (used by payroll/company selection)
    public function Get_employees_by_company(Request $request)
    {
        if (! Schema::hasTable('employees')) {
            return response()->json(collect([]));
        }

        $companyId = $request->get('id') ?? $request->get('company_id');
        if (! $companyId) {
            return response()->json(collect([]));
        }

        $employees = Employee::where('company_id', $companyId)
            ->where('deleted_at', '=', null)
            ->orderBy('id', 'desc')
            ->get(['id','username','firstname','lastname','basic_salary'])
            ->map(function($e){
                $label = $e->username ?: trim((($e->firstname ?? '') . ' ' . ($e->lastname ?? '')));
                return [
                    'id' => $e->id,
                    'username' => $e->username,
                    'firstname' => $e->firstname,
                    'lastname' => $e->lastname,
                    'basic_salary' => $e->basic_salary ?? 0,
                    'name' => $label ?: ('Employee #'.$e->id),
                ];
            });

        // Add company metadata so frontend can use overrides/defaults
        $companyMeta = null;
        if (Schema::hasTable('companies')) {
            $company = \App\Models\Company::where('id', $companyId)->first();
            if ($company) {
                $companyMeta = [
                    'id' => $company->id,
                    'name' => $company->name,
                    'nssf_percent' => $company->nssf_percent ?? null,
                    'shif_percent' => $company->shif_percent ?? null,
                    'housing_percent' => $company->housing_percent ?? null,
                    'tax_percent' => $company->tax_percent ?? null,
                    'personal_relief' => $company->personal_relief ?? null,
                ];
            }
        }

        // Include business users attached to this business/company. For each user, ensure
        // an Employee record exists (create a minimal one if missing) so the payroll UI
        // can select them. This prevents duplicate names in the response.
        if (Schema::hasTable('users')) {
            $users = User::where('business_id', $companyId)
                ->whereNull('deleted_at')
                ->orderBy('id', 'desc')
                ->get(['id', 'username', 'first_name', 'last_name', 'surname', 'email']);

            foreach ($users as $u) {
                // Try to find existing employee by email or username
                $emp = null;
                if (!empty($u->email)) {
                    $emp = Employee::where('email', $u->email)->whereNull('deleted_at')->first();
                }
                if (!$emp && !empty($u->username)) {
                    $emp = Employee::where('username', $u->username)->whereNull('deleted_at')->first();
                }

                if (! $emp) {
                    // Create a minimal employee record mapped from user fields
                    $firstname = $u->first_name ?? $u->surname ?? '';
                    $lastname = $u->last_name ?? $u->surname ?? '';
                    $username = $u->username ?: trim(($firstname . ' ' . $lastname));

                    $emp = Employee::create([
                        'firstname' => $firstname ?: 'User',
                        'lastname' => $lastname ?: ('#' . $u->id),
                        'username' => $username,
                        'email' => $u->email,
                        'company_id' => $companyId,
                        'basic_salary' => 0,
                    ]);
                } else {
                    // If employee exists but company_id empty, attach to this company
                    if (empty($emp->company_id)) {
                        $emp->company_id = $companyId;
                        $emp->save();
                    }
                }

                // Append to response if not already present
                $exists = $employees->firstWhere('id', $emp->id);
                if (! $exists) {
                    $label = $emp->username ?: trim((($emp->firstname ?? '') . ' ' . ($emp->lastname ?? '')));
                    $employees->push([
                        'id' => $emp->id,
                        'username' => $emp->username,
                        'firstname' => $emp->firstname,
                        'lastname' => $emp->lastname,
                        'basic_salary' => $emp->basic_salary ?? 0,
                        'name' => $label ?: ('Employee #'.$emp->id),
                    ]);
                }
            }
        }

        // Return an object with employees array and company meta for frontend defaults
        return response()->json([
            'employees' => $employees,
            'company' => $companyMeta,
        ]);
    }


    public function Get_office_shift_by_company(Request $request)
    {
        if (! Schema::hasTable('office_shifts')) {
            return response()->json(collect([]));
        }

        $office_shifts = OfficeShift::where('company_id' , $request->id)->where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']);

        return response()->json($office_shifts);
    }



    //details employee


    public function update_social_profile(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'update', Employee::class);

        $data = [];
        $data['skype'] = $request['skype'];
        $data['facebook'] = $request['facebook'];
        $data['whatsapp'] = $request['whatsapp'];
        $data['twitter'] = $request['twitter'];
        $data['linkedin'] = $request['linkedin'];

        Employee::whereId($id)->update($data);

        return response()->json(['success' => true]);
    }



       //-------------------- get_experiences_by_employee -------------\\

       public function get_experiences_by_employee(request $request)
       {
   
           $this->authorizeForUser($this->getAuthUser($request), 'view', Employee::class);
           // How many items do you want to display.
           $perPage = $request->limit;
           $pageStart = \Request::get('page', 1);
           // Start displaying items from this number;
           $offSet = ($pageStart * $perPage) - $perPage;
   
            $experiences = EmployeeExperience::where('employee_id' , $request->id)
            ->where('deleted_at', '=', null)
            ->orderBy('id', 'desc');

   
           $totalRows = $experiences->count();
           if($perPage == "-1"){
               $perPage = $totalRows;
           }
           $experiences = $experiences->offset($offSet)
               ->limit($perPage)
               ->orderBy('id', 'desc')
               ->get();
   
          
           return response()->json([
               'totalRows' => $totalRows,
               'experiences' => $experiences,
           ]);
   
       }

         //-------------------- get_accounts_by_employee -------------\\

         public function get_accounts_by_employee(request $request)
         {
     
             $this->authorizeForUser($this->getAuthUser($request), 'view', Employee::class);
             // How many items do you want to display.
             $perPage = $request->limit;
             $pageStart = \Request::get('page', 1);
             // Start displaying items from this number;
             $offSet = ($pageStart * $perPage) - $perPage;
     
              $accounts_bank = EmployeeAccount::where('employee_id' , $request->id)
              ->where('deleted_at', '=', null)
              ->orderBy('id', 'desc');
  
     
             $totalRows = $accounts_bank->count();
             if($perPage == "-1"){
                 $perPage = $totalRows;
             }
             $accounts_bank = $accounts_bank->offset($offSet)
                 ->limit($perPage)
                 ->orderBy('id', 'desc')
                 ->get();
     
            
             return response()->json([
                 'totalRows' => $totalRows,
                 'accounts_bank' => $accounts_bank,
             ]);
     
         }


}
