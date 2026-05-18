<?php

namespace Modules\Hrm\Http\Controllers;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Holiday;
use Carbon\Carbon;
use DateTime;
use Exception;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\utils\helpers;

class AttendancesController extends Controller
{
    protected function getAuthUser($request)
    {
        return $request->user('api') ?? $request->user() ?? auth()->user();
    }

    //----------- GET ALL  Attendance --------------\\

    public function index(Request $request)
    {
        $user = $this->getAuthUser($request);
        $this->authorizeForUser($user, 'view', Attendance::class);

        $businessId = session('business.id') ?? ($user->business_id ?? null);
        $this->syncSystemUserAttendanceFromActivityLog($businessId);

        $canViewAllRecords = false;
        if ($user) {
            $businessId = session('business.id') ?? $user->business_id;
            $canViewAllRecords = $user->hasRole('Admin#' . $businessId)
                || $user->can('hrm.access')
                || $user->can('hrm.attendances')
                || $user->can('attendance.view');
        }

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $data = array();
        $attendances = Attendance::with('employee', 'employee.company', 'company')
        ->where('deleted_at', '=', null)
        ->where(function ($query) use ($canViewAllRecords, $user) {
            if (! $canViewAllRecords && $user) {
                return $query->where('user_id', '=', $user->id);
            }
        })
         // Search With Multiple Param
         ->where(function ($query) use ($request) {
            return $query->when($request->filled('search'), function ($query) use ($request) {
                return $query->where('date', 'LIKE', "%{$request->search}%")
                    ->orWhere(function ($query) use ($request) {
                        return $query->whereHas('employee', function ($q) use ($request) {
                            $q->where('username', 'LIKE', "%{$request->search}%");
                        });
                    })
                    ->orWhere(function ($query) use ($request) {
                        return $query->whereHas('company', function ($q) use ($request) {
                            $q->where('name', 'LIKE', "%{$request->search}%");
                        });
                    });
            });
        });
        if (!in_array(strtolower($dir ?? ''), ['asc', 'desc'])) {
            $dir = 'desc';
        }
        $allowed = ['id', 'date', 'clock_in', 'clock_out', 'total_work', 'company_id', 'employee_id', 'created_at'];
        if (empty($order) || !in_array($order, $allowed)) {
            $order = 'id';
        }

        $totalRows = $attendances->count();
        if ($perPage == "-1") {
            $perPage = $totalRows;
        }

        if (is_numeric($perPage) && intval($perPage) > 0) {
            $attendances = $attendances->offset($offSet)
                ->limit(intval($perPage))
                ->orderBy($order, $dir)
                ->get();
        } else {
            $attendances = $attendances->orderBy($order, $dir)->get();
        }

        foreach ($attendances as $attendance) {

            $item['id'] = $attendance->id;
            $item['date'] = $attendance->date;
            $item['clock_in'] = $attendance->clock_in;
            $item['clock_out'] = $attendance->clock_out;
            $item['total_work'] = $attendance->total_work;
            $item['company_id'] = optional($attendance['company'])->id ?: optional(optional($attendance['employee'])->company)->id;
            $item['employee_id'] = optional($attendance['employee'])->id;
            $item['company_name'] = optional($attendance['company'])->name ?: optional(optional($attendance['employee'])->company)->name;
            $item['employee_username'] = optional($attendance['employee'])->username;
            
            $data[] = $item;
        }

        if (! ($request->wantsJson() || $request->expectsJson())) {
            $companies = Company::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id', 'name']);
            $employees = Employee::where('deleted_at', '=', null)->orderBy('username')->get(['id', 'username', 'company_id']);

            return view('hrm::attendances.index', [
                'attendances' => $data,
                'companies' => $companies,
                'employees' => $employees,
                'totalRows' => $totalRows,
            ]);
        }


        return response()->json([
            'attendances' => $data,
            'totalRows'   => $totalRows,
        ]);
    }



    public function create(Request $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'create', Attendance::class);

        $companies = Company::where('deleted_at', '=', null)->get(['id','name']);
        $employees = Employee::where('deleted_at', '=', null)->orderBy('username')->get(['id', 'username', 'company_id']);

        if (! ($request->wantsJson() || $request->expectsJson())) {
            return view('hrm::attendances.create', compact('companies', 'employees'));
        }

        return response()->json([
            'companies' =>$companies,
            'employees' => $employees,
        ]);

    }

    //----------- Store new Attendance --------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'create', Attendance::class);

        $this->validate($request, [
            'company_id'     => 'required',
            'employee_id'    => 'required',
            'date'           => 'required',
            'clock_in'       => 'required',
            'clock_out'      => 'required',
        ]);

        $user_auth = auth()->user();
        $data['user_id'] = $user_auth->id;

        $employee_id  = $request->employee_id;
        $date  = $request->date;
        $company_id  = $request->company_id;
        $clock_in  = $request->clock_in;
        $clock_out  = $request->clock_out;

        try{
            // Parse clock times (might be H:i, H:iA, or other format); normalize via strtotime
            $ci_ts  = strtotime($clock_in);
            $co_ts  = strtotime($clock_out);
            if ($ci_ts === false || $co_ts === false) {
                throw new \Exception('Invalid clock time format');
            }
            $clock_in  = new DateTime(date('Y-m-d H:i', $ci_ts));
            $clock_out  = new DateTime(date('Y-m-d H:i', $co_ts));
        }catch(Exception $e){
            if (! ($request->wantsJson() || $request->expectsJson())) {
                return back()->withInput()->with('error', 'Invalid clock times. Please enter valid times.');
            }
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        
        $employee = Employee::with('office_shift')->findOrFail($employee_id);
        
        $day_now = Carbon::parse($request->date)->format('l');
        $day_in_now = strtolower($day_now) . '_in';
        $day_out_now = strtolower($day_now) . '_out';

        // Null-safe: if employee has no shift assigned, treat as no shift constraint
        $shift_in  = $employee->office_shift ? $employee->office_shift->$day_in_now : null;
        $shift_out = $employee->office_shift ? $employee->office_shift->$day_out_now : null;
        if($shift_in == null){
            $data['employee_id'] = $employee_id;
            $data['company_id'] = $company_id;
            $data['date'] = $date;
            $data['clock_in'] = $clock_in->format('H:i');
            $data['clock_out'] = $clock_out->format('H:i');
            $data['status'] = 'present';

            $work_duration = $clock_in->diff($clock_out)->format('%H:%I');
            $data['total_work'] = $work_duration;
            $data['depart_early'] = '00:00';
            $data['late_time'] = '00:00';
            $data['overtime'] = '00:00';
            $data['clock_in_out'] = 0;

            Attendance::create($data);

            if (! ($request->wantsJson() || $request->expectsJson())) {
                return redirect()->route('hrm.attendances.index')->with('success', 'Created successfully');
            }

            return response()->json(['success' => true]);
        }

            try{
                // Times might be in various formats (H:i, H:iA, etc); normalize via strtotime.
                // Strip am/pm suffix when hour >= 13 to handle incorrect '17:00pm' storage.
                $normalize = function (string $t): int {
                    $ts = strtotime($t);
                    if ($ts === false) {
                        // Try stripping trailing am/pm
                        $ts = strtotime(preg_replace('/\s*[aApP][mM]$/', '', trim($t)));
                    }
                    if ($ts === false) {
                        throw new \Exception('Invalid shift time format: ' . $t);
                    }
                    return $ts;
                };
                $shift_in_ts   = $normalize($shift_in);
                $shift_out_ts  = $normalize($shift_out);
                $shift_in  = new DateTime(date('Y-m-d H:i', $shift_in_ts));
                $shift_out  = new DateTime(date('Y-m-d H:i', $shift_out_ts));
            }catch(Exception $e){
                if (! ($request->wantsJson() || $request->expectsJson())) {
                    return back()->withInput()->with('error', 'Invalid shift times. Please contact admin.');
                }
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }

            if($clock_in > $shift_in){
                $time_diff = $shift_in->diff($clock_in)->format('%H:%I');
                $data['clock_in'] = $clock_in->format('H:i');
                $data['late_time'] = $time_diff;
            }else{
                $data['clock_in'] = $shift_in->format('H:i');
                $data['late_time'] = '00:00';
            }


            if($clock_out < $shift_out){
                $time_diff = $shift_out->diff($clock_out)->format('%H:%I');
                $data['clock_out'] = $clock_out->format('H:i');
                $data['depart_early'] = $time_diff;
                $data['overtime'] = '00:00';

            }elseif($clock_out > $shift_out){
                $time_diff = $shift_out->diff($clock_out)->format('%H:%I');
                $data['clock_out'] = $clock_out->format('H:i');
                $data['overtime'] = $time_diff;
                $data['depart_early'] = '00:00';
            }else{
                $data['clock_out'] = $shift_out->format('H:i');
                $data['overtime'] = '00:00';
                $data['depart_early'] = '00:00';
            }

            $data['status'] = 'present';
            $work_duration = $clock_in->diff($clock_out)->format('%H:%I');
            $data['total_work'] = $work_duration;
            $data['clock_in_out'] = 0;
            $data['company_id'] = $company_id;
            $data['employee_id'] = $employee_id;
            $data['date'] = $date;

            $data['clock_in_ip'] = '';
            $data['clock_out_ip'] = '';


        Attendance::create($data);

        if (! ($request->wantsJson() || $request->expectsJson())) {
            return redirect()->route('hrm.attendances.index')->with('success', 'Created successfully');
        }

        return response()->json(['success' => true]);
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
    }

    //------------ function edit -----------\\

    public function edit(Request $request , $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'update', Attendance::class);

        $companies = Company::where('deleted_at', '=', null)->get(['id','name']);
        $attendance = Attendance::findOrFail($id);
        $employees = Employee::where('deleted_at', '=', null)->orderBy('username')->get(['id', 'username', 'company_id']);

        if (! ($request->wantsJson() || $request->expectsJson())) {
            return view('hrm::attendances.edit', compact('companies', 'attendance', 'employees'));
        }

        return response()->json([
            'companies' =>$companies,
            'attendance' => $attendance,
            'employees' => $employees,
        ]);

    }

    //-----------Update Attendance --------------\\

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'update', Attendance::class);

        $this->validate($request, [
            'company_id'      => 'required',
            'employee_id'      => 'required',
            'date'           => 'required',
            'clock_in'      => 'required',
            'clock_out'      => 'required',
        ]);

        $employee_id  = $request->employee_id;
        $date  = $request->date;
        $company_id = $request->company_id;
        $clock_in  = $request->clock_in;
        $clock_out  = $request->clock_out;

        try{
            // Parse clock times (might be H:i, H:iA, or other format); normalize via strtotime
            $ci_ts  = strtotime($clock_in);
            $co_ts  = strtotime($clock_out);
            if ($ci_ts === false || $co_ts === false) {
                throw new \Exception('Invalid clock time format');
            }
            $clock_in  = new DateTime(date('Y-m-d H:i', $ci_ts));
            $clock_out  = new DateTime(date('Y-m-d H:i', $co_ts));
        }catch(Exception $e){
            if (! ($request->wantsJson() || $request->expectsJson())) {
                return back()->withInput()->with('error', 'Invalid clock times. Please enter valid times.');
            }
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        $day_now = Carbon::parse($request->date)->format('l');
    
        $employee = Employee::with('office_shift')->findOrFail($employee_id);
        
        $day_in_now = strtolower($day_now) . '_in';
        $day_out_now = strtolower($day_now) . '_out';

        // Null-safe: if employee has no shift assigned, treat as no shift constraint
        $shift_in  = $employee->office_shift ? $employee->office_shift->$day_in_now : null;
        $shift_out = $employee->office_shift ? $employee->office_shift->$day_out_now : null;

        if($shift_in ==null){
            $data['employee_id'] = $employee_id;
            $data['company_id'] = $company_id;
            $data['date'] = $date;
            $data['clock_in'] = $clock_in->format('H:i');
            $data['clock_out'] = $clock_out->format('H:i');
            $data['status'] = 'present';

            $work_duration = $clock_in->diff($clock_out)->format('%H:%I');
            $data['total_work'] = $work_duration;
            $data['depart_early'] = '00:00';
            $data['late_time'] = '00:00';
            $data['overtime'] = '00:00';
            $data['clock_in_out'] = 0;

            Attendance::find($id)->update($data);

            if (! ($request->wantsJson() || $request->expectsJson())) {
                return redirect()->route('hrm.attendances.index')->with('success', 'Updated successfully');
            }

            return response()->json(['success' => true]);

        }

        try{
            // Times might be in various formats (H:i, H:iA, etc); normalize via strtotime
            $shift_in_ts   = strtotime($shift_in);
            $shift_out_ts  = strtotime($shift_out);
            if ($shift_in_ts === false || $shift_out_ts === false) {
                throw new \Exception('Invalid shift time format');
            }
            $shift_in  = new DateTime(date('Y-m-d H:i', $shift_in_ts));
            $shift_out  = new DateTime(date('Y-m-d H:i', $shift_out_ts));
        }catch(Exception $e){
            if (! ($request->wantsJson() || $request->expectsJson())) {
                return back()->withInput()->with('error', 'Invalid shift times. Please contact admin.');
            }
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        $data['employee_id'] = $employee_id;
        $data['date'] = $date;

        if($clock_in > $shift_in){
            $time_diff = $shift_in->diff($clock_in)->format('%H:%I');
            $data['clock_in'] = $clock_in->format('H:i');
            $data['late_time'] = $time_diff;
        }else{
            $data['clock_in'] = $shift_in->format('H:i');
            $data['late_time'] = '00:00';
        }


        if($clock_out < $shift_out){
            $time_diff = $shift_out->diff($clock_out)->format('%H:%I');
            $data['clock_out'] = $clock_out->format('H:i');
            $data['depart_early'] = $time_diff;
            $data['overtime'] = '00:00';

        }elseif($clock_out > $shift_out){
            $time_diff = $shift_out->diff($clock_out)->format('%H:%I');
            $data['clock_out'] = $clock_out->format('H:i');
            $data['overtime'] = $time_diff;
            $data['depart_early'] = '00:00';
        }else{
            $data['clock_out'] = $shift_out->format('H:i');
            $data['overtime'] = '00:00';
            $data['depart_early'] = '00:00';
        }

        $data['status'] = 'present';
        $work_duration = $clock_in->diff($clock_out)->format('%H:%I');
        $data['total_work'] = $work_duration;
        $data['clock_in_out'] = 0;
        $data['company_id'] = $company_id;


        Attendance::find($id)->update($data);

        if (! ($request->wantsJson() || $request->expectsJson())) {
            return redirect()->route('hrm.attendances.index')->with('success', 'Updated successfully');
        }

        return response()->json(['success' => true]);
    }

    //----------- Delete  Attendance --------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'delete', Attendance::class);

            Attendance::whereId($id)->update([
                'deleted_at' => Carbon::now(),
            ]);


        if (! ($request->wantsJson() || $request->expectsJson())) {
            return redirect()->route('hrm.attendances.index')->with('success', 'Deleted successfully');
        }

        return response()->json(['success' => true]);
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

        $this->authorizeForUser($this->getAuthUser($request), 'delete', Attendance::class);

        $selectedIds = $request->selectedIds;
        foreach ($selectedIds as $attendance_id) {
            Attendance::whereId($attendance_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * For employees who are system users, infer attendance from login/logout events.
     * Login creates/updates clock_in for today; logout updates clock_out.
     */
    private function syncSystemUserAttendanceFromActivityLog(?int $businessId): void
    {
        if (
            ! Schema::hasTable('activity_log')
            || ! Schema::hasTable('users')
            || ! Schema::hasTable('employees')
            || ! Schema::hasTable('attendances')
        ) {
            return;
        }

        $today = Carbon::today()->toDateString();

        $systemUsers = DB::table('users')
            ->whereNull('deleted_at')
            ->where('allow_login', 1)
            ->when($businessId && Schema::hasColumn('users', 'business_id'), function ($q) use ($businessId) {
                return $q->where('business_id', $businessId);
            })
            ->get(['id', 'username', 'email']);

        if ($systemUsers->isEmpty()) {
            return;
        }

        $usersByEmail = [];
        $usersByUsername = [];
        foreach ($systemUsers as $u) {
            if (! empty($u->email)) {
                $usersByEmail[strtolower(trim($u->email))] = $u;
            }
            if (! empty($u->username)) {
                $usersByUsername[strtolower(trim($u->username))] = $u;
            }
        }

        $employees = DB::table('employees')
            ->whereNull('deleted_at')
            ->whereNull('leaving_date')
            ->when($businessId && Schema::hasColumn('employees', 'business_id'), function ($q) use ($businessId) {
                return $q->where(function ($tenantQ) use ($businessId) {
                    $tenantQ->where('business_id', $businessId)
                        ->orWhereNull('business_id');
                });
            })
            ->get(['id', 'username', 'email', 'company_id']);

        if ($employees->isEmpty()) {
            return;
        }

        $employeeByUserId = [];
        foreach ($employees as $emp) {
            $match = null;
            if (! empty($emp->email)) {
                $match = $usersByEmail[strtolower(trim($emp->email))] ?? null;
            }
            if (! $match && ! empty($emp->username)) {
                $match = $usersByUsername[strtolower(trim($emp->username))] ?? null;
            }

            if ($match) {
                $employeeByUserId[$match->id] = $emp;
            }
        }

        if (empty($employeeByUserId)) {
            return;
        }

        $activityRows = DB::table('activity_log')
            ->where('causer_type', 'App\\User')
            ->whereIn('causer_id', array_keys($employeeByUserId))
            ->whereDate('created_at', $today)
            ->when($businessId && Schema::hasColumn('activity_log', 'business_id'), function ($q) use ($businessId) {
                return $q->where('business_id', $businessId);
            })
            ->select(
                'causer_id',
                DB::raw("MIN(CASE WHEN description = 'login' THEN created_at END) as first_login"),
                DB::raw("MAX(CASE WHEN description = 'logout' THEN created_at END) as last_logout")
            )
            ->groupBy('causer_id')
            ->get();

        foreach ($activityRows as $row) {
            $emp = $employeeByUserId[$row->causer_id] ?? null;
            if (! $emp || empty($row->first_login)) {
                continue;
            }

            $clockIn = Carbon::parse($row->first_login)->format('H:i:s');
            $clockOut = ! empty($row->last_logout) ? Carbon::parse($row->last_logout)->format('H:i:s') : null;

            $existing = DB::table('attendances')
                ->where('employee_id', $emp->id)
                ->whereDate('date', $today)
                ->whereNull('deleted_at')
                ->first();

            if ($existing) {
                $update = [];

                if (empty($existing->clock_in) || $clockIn < $existing->clock_in) {
                    $update['clock_in'] = $clockIn;
                }

                if ($clockOut && (empty($existing->clock_out) || $clockOut > $existing->clock_out)) {
                    $update['clock_out'] = $clockOut;
                }

                if (Schema::hasColumn('attendances', 'duration_minutes')) {
                    $effectiveClockIn = $update['clock_in'] ?? $existing->clock_in;
                    $effectiveClockOut = $update['clock_out'] ?? $existing->clock_out;
                    if (! empty($effectiveClockIn) && ! empty($effectiveClockOut)) {
                        $in = Carbon::parse($today . ' ' . $effectiveClockIn);
                        $out = Carbon::parse($today . ' ' . $effectiveClockOut);
                        $update['duration_minutes'] = max(0, $in->diffInMinutes($out));
                    }
                }

                if (Schema::hasColumn('attendances', 'status')) {
                    $update['status'] = 'present';
                }
                if (Schema::hasColumn('attendances', 'company_id') && ! empty($emp->company_id)) {
                    $update['company_id'] = $emp->company_id;
                }
                if (Schema::hasColumn('attendances', 'updated_at')) {
                    $update['updated_at'] = now();
                }

                if (! empty($update)) {
                    DB::table('attendances')->where('id', $existing->id)->update($update);
                }
            } else {
                $insert = [
                    'employee_id' => $emp->id,
                    'date' => $today,
                    'clock_in' => $clockIn,
                ];

                if ($clockOut) {
                    $insert['clock_out'] = $clockOut;
                }
                if (Schema::hasColumn('attendances', 'business_id')) {
                    $insert['business_id'] = $businessId;
                }
                if (Schema::hasColumn('attendances', 'company_id') && ! empty($emp->company_id)) {
                    $insert['company_id'] = $emp->company_id;
                }
                if (Schema::hasColumn('attendances', 'status')) {
                    $insert['status'] = 'present';
                }
                if (Schema::hasColumn('attendances', 'duration_minutes') && $clockOut) {
                    $insert['duration_minutes'] = max(0, Carbon::parse($today . ' ' . $clockIn)->diffInMinutes(Carbon::parse($today . ' ' . $clockOut)));
                }
                if (Schema::hasColumn('attendances', 'created_at')) {
                    $insert['created_at'] = now();
                }
                if (Schema::hasColumn('attendances', 'updated_at')) {
                    $insert['updated_at'] = now();
                }

                DB::table('attendances')->insert($insert);
            }
        }
    }

}
