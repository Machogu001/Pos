<?php

namespace Modules\Hrm\Http\Controllers;
use App\Http\Controllers\Controller;

use App\Models\Employee;
use App\Models\Company;
use App\Models\Designation;
use App\Models\EmployeeAccount;
use App\Models\Department;
use App\Models\OfficeShift;
use App\utils\helpers;
use App\Business;
use App\User;
use App\Services\Hrm\SystemUserEmployeeSyncService;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Modules\Hrm\Http\Controllers\Concerns\AuditsHrmActions;
use Modules\Hrm\Http\Requests\StoreEmployeeRequest;
use Modules\Hrm\Http\Requests\UpdateEmployeeRequest;

class EmployeesController extends Controller
{
    use AuditsHrmActions;

    protected function scopeUsersToBusiness($query, ?int $businessId)
    {
        if ($businessId && Schema::hasColumn('users', 'business_id')) {
            $query->where('business_id', $businessId);
        }

        if (Schema::hasColumn('users', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (Schema::hasColumn('users', 'allow_login')) {
            $query->where('allow_login', 1);
        }

        if (Schema::hasColumn('users', 'status')) {
            $query->where('status', 'active');
        }

        if (Schema::hasColumn('users', 'user_type')) {
            $query->whereIn('user_type', ['admin', 'user']);
        }

        return $query;
    }

    protected function findMatchingEmployeeForUser(User $user, ?int $businessId)
    {
        if (! Schema::hasTable('employees')) {
            return null;
        }

        if (empty($user->email) && empty($user->username)) {
            return null;
        }

        $query = Employee::query();
        $query = $this->scopeEmployeesToBusiness($query, $businessId);
        $query->where(function ($employeeQuery) use ($user) {
            if (! empty($user->email)) {
                $employeeQuery->orWhere('email', $user->email);
            }
            if (! empty($user->username)) {
                $employeeQuery->orWhere('username', $user->username);
            }
        });

        if (Schema::hasColumn('employees', 'deleted_at')) {
            $query->orderByRaw('deleted_at is null desc');
        }

        return $query->orderByDesc('id')->first();
    }

    protected function availableExistingUsers(?int $businessId)
    {
        if (! Schema::hasTable('users')) {
            return collect([]);
        }

        return $this->scopeUsersToBusiness(User::query(), $businessId)
            ->orderBy('first_name')
            ->orderBy('username')
            ->get(['id', 'username', 'first_name', 'last_name', 'surname', 'email', 'contact_no', 'contact_number', 'alt_number', 'gender'])
            ->filter(function ($user) use ($businessId) {
                $employee = $this->findMatchingEmployeeForUser($user, $businessId);

                return ! $employee || ! empty($employee->deleted_at);
            })
            ->map(function ($user) {
                $firstName = trim((string) ($user->first_name ?? ''));
                $lastName = trim((string) ($user->last_name ?? ($user->surname ?? '')));
                $fullName = trim($firstName . ' ' . $lastName);
                $phone = $user->contact_no ?? $user->contact_number ?? $user->alt_number ?? '';
                $labelParts = array_filter([
                    $fullName,
                    $user->username,
                    $user->email,
                ]);

                return [
                    'id' => $user->id,
                    'firstname' => $firstName ?: ($user->username ?: 'User'),
                    'lastname' => $lastName ?: 'User',
                    'email' => $user->email,
                    'phone' => $phone,
                    'gender' => in_array(strtolower((string) $user->gender), ['male', 'female', 'other'], true) ? strtolower((string) $user->gender) : 'male',
                    'label' => implode(' - ', $labelParts) ?: ('User #' . $user->id),
                ];
            })
            ->values();
    }

    protected function employeePayloadFromRequest(Request $request, ?User $selectedUser = null): array
    {
        $firstname = trim((string) $request->input('firstname'));
        $lastname = trim((string) $request->input('lastname'));
        $selectedGender = strtolower((string) ($request->input('gender') ?: ($selectedUser->gender ?? 'male')));
        if (! in_array($selectedGender, ['male', 'female', 'other'], true)) {
            $selectedGender = 'male';
        }

        return $this->setEmployeeBusinessId([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'username' => $selectedUser && ! empty($selectedUser->username) ? $selectedUser->username : trim($firstname . ' ' . $lastname),
            'email' => $request->input('email') ?: ($selectedUser->email ?? null),
            'gender' => $selectedGender,
            'phone' => $request->input('phone') ?: ($selectedUser->contact_no ?? $selectedUser->contact_number ?? $selectedUser->alt_number ?? null),
            'birth_date' => $request->birth_date,
            'country' => $request->country,
            'address' => $request->address,
            'city' => $request->city,
            'province' => $request->province,
            'zipcode' => $request->zipcode,
            'marital_status' => $request->marital_status,
            'employment_type' => $request->employment_type,
            'basic_salary' => $request->basic_salary,
            'hourly_rate' => $request->hourly_rate,
            'company_id' => $request->company_id,
            'department_id' => $request->department_id,
            'designation_id' => $request->designation_id,
            'office_shift_id' => $request->office_shift_id,
            'joining_date' => $request->joining_date,
            'total_leave' => $request->input('total_leave'),
            'remaining_leave' => $request->input('remaining_leave'),
        ], session('business.id'));
    }

    protected function scopeEmployeesToBusiness($query, ?int $businessId)
    {
        if (! $businessId || ! Schema::hasColumn('employees', 'business_id')) {
            return $query;
        }

        return $query->where(function ($tenantQ) use ($businessId) {
            $tenantQ->where('business_id', $businessId)
                ->orWhereNull('business_id');
        });
    }

    protected function setEmployeeBusinessId(array $payload, ?int $businessId): array
    {
        if ($businessId && Schema::hasColumn('employees', 'business_id')) {
            $payload['business_id'] = $businessId;
        }

        return $payload;
    }


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
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.employees'))) {
            abort(403);
        }

        // Keep HRM employee list aligned with active system login users.
        app(SystemUserEmployeeSyncService::class)->sync(session('business.id'));
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

        $businessId = session('business.id');

        $employees = Employee::when(!empty($with), function ($q) use ($with) {
                return $q->with($with);
            })
            ->where('deleted_at', '=', null)
            ->where('leaving_date', null);

        $employees = $this->scopeEmployeesToBusiness($employees, $businessId);

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
                $item['is_system_user'] = (bool) ($employee->is_system_user ?? false);
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
            $item['is_system_user'] = (bool) ($employee->is_system_user ?? false);
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
          $user = $this->getAuthUser($request);
          if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.employees'))) {
              abort(403);
          }

          $businessId = session('business.id');
          if (Schema::hasTable('business')) {
              $companies = Business::orderBy('id', 'desc')->get(['id','name']);
          } else {
              $companies = Schema::hasTable('companies') ? Company::where('deleted_at', '=', null)->get(['id','name']) : collect([]);
          }
          $departments = Schema::hasTable('departments') ? Department::where('deleted_at', '=', null)->get(['id','department']) : collect([]);
          $designations = Schema::hasTable('designations') ? Designation::where('deleted_at', '=', null)->get(['id','designation']) : collect([]);
          $office_shifts = Schema::hasTable('office_shifts') ? OfficeShift::where('deleted_at', '=', null)->get(['id','name']) : collect([]);
          $existing_users = $this->availableExistingUsers($businessId);

          // If the browser requested HTML, return a blade form. Otherwise return JSON (API clients).
          if (! $request->wantsJson()) {
              return view('hrm::employees.create', compact('companies', 'departments', 'designations', 'office_shifts', 'existing_users'));
          }

          return response()->json([
              'companies' => $companies,
              'departments' => $departments,
              'designations' => $designations,
              'office_shifts' => $office_shifts,
              'existing_users' => $existing_users,
          ]);
      }


  //----------------Store  Employee ---------------\\

    public function store(StoreEmployeeRequest $request)
    {
        $selectedUser = null;
        if ($request->filled('existing_user_id') && Schema::hasTable('users')) {
            $selectedUser = $this->scopeUsersToBusiness(User::query(), session('business.id'))
                ->where('id', $request->input('existing_user_id'))
                ->first();
        }

        $defaultLeave   = config('hrm.default_annual_leave', 21);
        $totalLeave     = $request->filled('total_leave') ? intval($request->total_leave) : $defaultLeave;
        $remainingLeave = $request->filled('remaining_leave') ? intval($request->remaining_leave) : $totalLeave;
        $remainingLeave = min($totalLeave, max(0, $remainingLeave));

        $payload = $this->employeePayloadFromRequest($request, $selectedUser);
        $payload['total_leave'] = $totalLeave;
        $payload['remaining_leave'] = $remainingLeave;

        $emp = null;
        if ($selectedUser) {
            $emp = $this->findMatchingEmployeeForUser($selectedUser, session('business.id'));
        }

        if ($emp) {
            if (Schema::hasColumn('employees', 'deleted_at')) {
                $payload['deleted_at'] = null;
            }
            $emp->update($payload);
            $emp->refresh();
        } else {
            $emp = Employee::create($payload);
        }

        $this->logHrmAudit('hrm.employee.created', [
            'employee_id' => $emp->id,
            'company_id' => $emp->company_id,
            'department_id' => $emp->department_id,
            'designation_id' => $emp->designation_id,
        ], $emp);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true, 'employee' => $emp]);
        }

        return redirect()->route('hrm.employees.index')->with('success', 'Employee created successfully.');
    }

   
     //------------ function show -----------\\

      public function show(Request $request, $id)
      {
                $user = $this->getAuthUser($request);
                if (!$user || !$user->can('hrm.access')) {
                        abort(403);
                }

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
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.employees'))) {
            abort(403);
        }

        $employee = Employee::where('deleted_at', '=', null)->findOrFail($id);
        if (Schema::hasTable('business')) {
            $companies = Business::orderBy('id', 'desc')->get(['id','name']);
        } else {
            $companies = Company::where('deleted_at', '=', null)->get(['id','name']);
        }

        // Office shifts: prefer company-scoped, then fall back to all active shifts.
        if (Schema::hasTable('office_shifts')) {
            $officeShiftsQuery = OfficeShift::where('deleted_at', '=', null);
            if (!empty($employee->company_id)) {
                $officeShiftsQuery->where('company_id', $employee->company_id);
            }
            $office_shifts = $officeShiftsQuery->get(['id', 'name']);

            if ($office_shifts->isEmpty()) {
                $office_shifts = OfficeShift::where('deleted_at', '=', null)
                    ->orderBy('name', 'asc')
                    ->get(['id', 'name']);
            }

            // Keep current assignment selectable even if it falls outside filters.
            if (!empty($employee->office_shift_id) && !$office_shifts->pluck('id')->contains($employee->office_shift_id)) {
                $currentShift = OfficeShift::where('id', $employee->office_shift_id)->first(['id', 'name']);
                if ($currentShift) {
                    $office_shifts->prepend($currentShift);
                }
            }
        } else {
            $office_shifts = collect([]);
        }

        // Prefer company-scoped departments, but fall back to all departments so admin can assign one even
        // when the employee has no company or the company has no departments yet.
        if (Schema::hasTable('departments')) {
            $departments = Department::where('company_id' , $employee->company_id)->where('deleted_at', '=', null)->get(['id','department','company_id']);
            if ($departments->isEmpty()) {
                $departments = Department::where('deleted_at', '=', null)->orderBy('id','desc')->get(['id','department','company_id']);
            }
        } else {
            $departments = collect([]);
        }

        // Designations: prefer department-scoped, then company-scoped, then all active designations.
        if (Schema::hasTable('designations')) {
            $designationsQuery = Designation::where('deleted_at', '=', null);
            if (!empty($employee->department_id)) {
                $designationsQuery->where('department_id', $employee->department_id);
            }
            $designations = $designationsQuery->get(['id', 'designation', 'department_id']);

            if ($designations->isEmpty() && !empty($employee->company_id)) {
                $departmentIds = Department::where('company_id', $employee->company_id)
                    ->where('deleted_at', '=', null)
                    ->pluck('id');
                if ($departmentIds->isNotEmpty()) {
                    $designations = Designation::whereIn('department_id', $departmentIds)
                        ->where('deleted_at', '=', null)
                        ->get(['id', 'designation', 'department_id']);
                }
            }

            if ($designations->isEmpty()) {
                $designations = Designation::where('deleted_at', '=', null)
                    ->orderBy('designation', 'asc')
                    ->get(['id', 'designation', 'department_id']);
            }

            // Keep current assignment selectable even if it falls outside filters.
            if (!empty($employee->designation_id) && !$designations->pluck('id')->contains($employee->designation_id)) {
                $currentDesignation = Designation::where('id', $employee->designation_id)->first(['id', 'designation', 'department_id']);
                if ($currentDesignation) {
                    $designations->prepend($currentDesignation);
                }
            }
        } else {
            $designations = collect([]);
        }

        // If request expects JSON (AJAX/API), return JSON; otherwise render the classic Blade edit page
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'employee' => $employee,
                'companies' => $companies,
                'office_shifts' => $office_shifts,
                'departments' => $departments,
                'designations' => $designations,
            ]);
        }

        return view('hrm::employees.edit', compact('employee', 'companies', 'office_shifts', 'departments', 'designations'));
    }

    // Suspend or unsuspend payroll for an employee
    public function suspend(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.employees'))) {
            abort(403);
        }

        $employee = Employee::findOrFail($id);
        $action = $request->input('action', 'suspend');
        if ($action === 'unsuspend') {
            $employee->suspended = false;
        } else {
            $employee->suspended = true;
        }
        $employee->save();

        $this->logHrmAudit('hrm.employee.suspension_toggled', [
            'employee_id' => $employee->id,
            'suspended' => (bool) $employee->suspended,
            'action' => $action,
        ], $employee);

        return response()->json(['success' => true, 'suspended' => (bool) $employee->suspended]);
    }

    // Deductions: list for employee
    public function deductionsIndex(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.employees'))) {
            abort(403);
        }
        if (! Schema::hasTable('employee_deductions')) {
            return response()->json(['deductions' => []]);
        }
        $deductions = \DB::table('employee_deductions')->where('employee_id', $id)->orderBy('created_at', 'desc')->get();
        return response()->json(['deductions' => $deductions]);
    }

    // Deductions: store
    public function deductionsStore(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || !$user->can('hrm.access')) {
            abort(403);
        }
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
        $user = $this->getAuthUser($request);
        if (!$user || !$user->can('hrm.access')) {
            abort(403);
        }
        if (! Schema::hasTable('employee_deductions')) {
            return response()->json(['success' => false], 404);
        }
        \DB::table('employee_deductions')->where('id', $deduction_id)->where('employee_id', $employee_id)->delete();
        return response()->json(['success' => true]);
    }

     //---------------- UPDATE Employee -------------\\

     public function update(UpdateEmployeeRequest $request, $id)
     {
        $data = [
            'firstname'       => $request->firstname,
            'lastname'        => $request->lastname,
            'username'        => $request->firstname . ' ' . $request->lastname,
            'country'         => $request->country,
            'email'           => $request->email,
            'gender'          => $request->gender,
            'phone'           => $request->phone,
            'birth_date'      => $request->birth_date,
            'company_id'      => $request->company_id,
            'department_id'   => $request->department_id,
            'designation_id'  => $request->designation_id,
            'office_shift_id' => $request->office_shift_id,
            'joining_date'    => $request->joining_date,
            'notes'           => $request->notes,
            'role_users_id'   => $request->role_users_id,
            'leaving_date'    => $request->leaving_date ?: null,
            'marital_status'  => $request->marital_status,
            'employment_type' => $request->employment_type,
            'city'            => $request->city,
            'province'        => $request->province,
            'zipcode'         => $request->zipcode,
            'address'         => $request->address,
            'basic_salary'    => $request->basic_salary,
            'hourly_rate'     => $request->hourly_rate,
        ];

        // calculation of total_leave & remaining_leave with sanitization
        $employee_leave_info = Employee::find($id);
        $current_total = intval($employee_leave_info->total_leave ?? 0);
        $current_remaining = intval($employee_leave_info->remaining_leave ?? 0);
        $req_total = intval($request->total_leave);
        $req_remaining = intval($request->remaining_leave ?? $req_total);

        if ($current_total === 0) {
            // initialize both to requested total (or provided remaining bounded)
            $data['total_leave'] = $req_total;
            $data['remaining_leave'] = min(max($req_remaining, 0), $req_total);
        } elseif ($req_total > $current_total) {
            // increased entitlement: add the delta to remaining leave
            $delta = $req_total - $current_total;
            $data['total_leave'] = $req_total;
            $data['remaining_leave'] = min(max($current_remaining + $delta, 0), $req_total);
        } elseif ($req_total < $current_total) {
            // decreased entitlement: subtract the delta from remaining leave (but not below 0)
            $delta = $current_total - $req_total;
            $data['total_leave'] = $req_total;
            $data['remaining_leave'] = max($current_remaining - $delta, 0);
        } else {
            // same total: respect provided remaining (bounded)
            $data['total_leave'] = $req_total;
            $data['remaining_leave'] = min(max($req_remaining, 0), $req_total);
        }
        
            Employee::find($id)->update($data);

        $updatedEmployee = Employee::find($id);
        if ($updatedEmployee) {
            $this->logHrmAudit('hrm.employee.updated', [
                'employee_id' => $updatedEmployee->id,
                'company_id' => $updatedEmployee->company_id,
                'department_id' => $updatedEmployee->department_id,
                'designation_id' => $updatedEmployee->designation_id,
            ], $updatedEmployee);
        }

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('hrm.employees.show', $id)->with('success', 'Updated successfully');
     }

    //------------ Delete Employee -----------\\

    public function destroy(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.employees'))) {
            abort(403);
        }

        $update = [
            'deleted_at' => Carbon::now(),
        ];

        if (Schema::hasColumn('employees', 'sync_disabled')) {
            $update['sync_disabled'] = 1;
        }

        Employee::whereId($id)->update($update);

        $this->logHrmAudit('hrm.employee.soft_deleted', [
            'employee_id' => $id,
        ]);

        return response()->json(['success' => true]);
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.employees'))) {
            abort(403);
        }

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
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.employees'))) {
            abort(403);
        }

        $perPage = $request->limit ?? 10;
        $page = max(1, (int) $request->get('page', 1));

        $query = Employee::whereNotNull('deleted_at')->orderBy('deleted_at', 'desc');
        $query = $this->scopeEmployeesToBusiness($query, session('business.id'));
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
        $user = $this->getAuthUser($request);
        if (!$user || !$user->can('hrm.access')) {
            abort(403);
        }
        $emp = Employee::whereId($id)->first();
        if (! $emp) {
            return response()->json(['success' => false], 404);
        }
        $emp->deleted_at = null;
        $emp->save();

        $this->logHrmAudit('hrm.employee.restored', [
            'employee_id' => $emp->id,
        ], $emp);

        return response()->json(['success' => true]);
    }

    //--------------- Permanently delete employee ---------------\
    public function forceDelete(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || !$user->can('hrm.access')) {
            abort(403);
        }
        $emp = Employee::whereId($id)->first();
        if (! $emp) {
            return response()->json(['success' => false], 404);
        }
        $employeeSnapshotId = $emp->id;
        $emp->delete();

        $this->logHrmAudit('hrm.employee.force_deleted', [
            'employee_id' => $employeeSnapshotId,
        ]);

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

        $deductionTotals = collect([]);
        if (Schema::hasTable('employee_deductions')) {
            $deductionTotals = DB::table('employee_deductions')
                ->select('employee_id', DB::raw('SUM(amount) as total_amount'))
                ->groupBy('employee_id')
                ->pluck('total_amount', 'employee_id');
        }

        $employees = Employee::where('company_id', $companyId)
            ->where('deleted_at', '=', null)
            ->orderBy('id', 'desc')
            ->get(['id','username','firstname','lastname','basic_salary'])
            ->map(function($e) use ($deductionTotals){
                $label = $e->username ?: trim((($e->firstname ?? '') . ' ' . ($e->lastname ?? '')));
                return [
                    'id' => $e->id,
                    'username' => $e->username,
                    'firstname' => $e->firstname,
                    'lastname' => $e->lastname,
                    'basic_salary' => $e->basic_salary ?? 0,
                    'default_deductions' => round((float) ($deductionTotals[$e->id] ?? 0), 2),
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
            $users = User::when(Schema::hasColumn('users', 'business_id'), function ($query) use ($companyId) {
                    $query->where('business_id', $companyId);
                })
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

                    $emp = Employee::create($this->setEmployeeBusinessId([
                        'firstname' => $firstname ?: 'User',
                        'lastname' => $lastname ?: ('#' . $u->id),
                        'username' => $username,
                        'email' => $u->email,
                        'company_id' => $companyId,
                        'basic_salary' => 0,
                    ], session('business.id')));
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
                        'default_deductions' => round((float) ($deductionTotals[$emp->id] ?? 0), 2),
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
  
           $user = $this->getAuthUser($request);
           if (!$user || !$user->can('hrm.access')) {
               abort(403);
           }
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
     
             $user = $this->getAuthUser($request);
             if (!$user || !$user->can('hrm.access')) {
                 abort(403);
             }
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
