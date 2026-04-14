<?php

namespace Modules\Hrm\Http\Controllers;

use App\Services\Hrm\SystemUserEmployeeSyncService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class HrmController extends Controller
{
    /**
     * Display the HRM dashboard with key HR statistics and quick navigation.
     */
    public function index(Request $request)
    {
        $businessId = session('business.id');

        // Ensure dashboard counts reflect both manually added and system login users.
        app(SystemUserEmployeeSyncService::class)->sync($businessId);

        $today = Carbon::today()->toDateString();
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd = Carbon::now()->endOfMonth()->toDateString();

        $applyTenantScope = function ($query, string $table, ?string $aliasedColumn = null) use ($businessId) {
            if (! $businessId || ! Schema::hasTable($table) || ! Schema::hasColumn($table, 'business_id')) {
                return $query;
            }

            $column = $aliasedColumn ?: 'business_id';
            return $query->where(function ($tenantQ) use ($businessId, $column) {
                $tenantQ->where($column, $businessId)
                    ->orWhereNull($column);
            });
        };

        $stats = [
            'total_employees' => 0,
            'active_employees' => 0,
            'on_leave_today' => 0,
            'pending_leaves' => 0,
            'this_month_payroll' => 0.0,
            'total_companies' => 0,
            'total_departments' => 0,
            'today_attendance_records' => 0,
            'today_clocked_in' => 0,
            'absent_estimate_today' => 0,
        ];

        $recentPendingLeaves = collect([]);
        $recentPayrollRuns = collect([]);
        $clockedInToday = collect([]);
        $notClockedInToday = collect([]);
        $alerts = [];
        $inferredClockByEmployeeId = collect([]);
        $inferredClockedEmployeeIds = [];
        $activeEmployeesForInference = collect([]);
        $notClockedInTruncated = false;
        $notClockedDisplayLimit = 500;

        if (Schema::hasTable('companies')) {
            $companiesQuery = DB::table('companies')->whereNull('deleted_at');
            $companiesQuery = $applyTenantScope($companiesQuery, 'companies');
            $stats['total_companies'] = $companiesQuery->count();
        }

        if (Schema::hasTable('departments')) {
            $departmentsQuery = DB::table('departments')->whereNull('deleted_at');
            $departmentsQuery = $applyTenantScope($departmentsQuery, 'departments');
            $stats['total_departments'] = $departmentsQuery->count();
        }

        if (Schema::hasTable('employees')) {
            $employeesBase = DB::table('employees')->whereNull('deleted_at');
            $employeesBase = $applyTenantScope($employeesBase, 'employees');

            $stats['total_employees'] = (clone $employeesBase)->count();

            $stats['active_employees'] = (clone $employeesBase)
                ->whereNull('leaving_date')
                ->count();

            // Active employees dataset used for system-user attendance inference.
            $activeEmployeesForInference = (clone $employeesBase)
                ->whereNull('leaving_date')
                ->get(['id', 'firstname', 'lastname', 'username', 'email']);

            if (
                $activeEmployeesForInference->isNotEmpty()
                && Schema::hasTable('users')
                && Schema::hasTable('activity_log')
            ) {
                $systemUsers = DB::table('users')
                    ->whereNull('deleted_at')
                    ->where('allow_login', 1)
                    ->when($businessId && Schema::hasColumn('users', 'business_id'), function ($q) use ($businessId) {
                        return $q->where('business_id', $businessId);
                    })
                    ->get(['id', 'username', 'email']);

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

                $employeeByUserId = [];
                foreach ($activeEmployeesForInference as $emp) {
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

                if (! empty($employeeByUserId)) {
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
                        if (! $emp) {
                            continue;
                        }

                        // HRM rule: for system users, use explicit login/out events only.
                        $clockIn = $row->first_login;
                        if (! $clockIn) {
                            continue;
                        }

                        $clockOut = $row->last_logout;
                        $inferredClockByEmployeeId->put((int) $emp->id, (object) [
                            'id' => (int) $emp->id,
                            'firstname' => $emp->firstname,
                            'lastname' => $emp->lastname,
                            'username' => $emp->username,
                            'clock_in_time' => Carbon::parse($clockIn)->format('d m Y H:i:s'),
                            'clock_out_time' => $clockOut ? Carbon::parse($clockOut)->format('d m Y H:i:s') : null,
                        ]);
                    }

                    $inferredClockedEmployeeIds = $inferredClockByEmployeeId->keys()->map(fn($id) => (int) $id)->all();
                }
            }
        }

        if (Schema::hasTable('leaves')) {
            $leaveBase = DB::table('leaves')->whereNull('deleted_at');
            $leaveBase = $applyTenantScope($leaveBase, 'leaves');

            $stats['on_leave_today'] = (clone $leaveBase)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->count();

            $stats['pending_leaves'] = (clone $leaveBase)
                ->where('status', 'pending')
                ->count();

            $recentPendingLeavesQuery = DB::table('leaves as l')
                ->leftJoin('employees as e', 'e.id', '=', 'l.employee_id')
                ->whereNull('l.deleted_at')
                ->where('l.status', 'pending')
                ->when($businessId && Schema::hasColumn('leaves', 'business_id'), function ($q) use ($businessId) {
                    return $q->where(function ($tenantQ) use ($businessId) {
                        $tenantQ->where('l.business_id', $businessId)
                            ->orWhereNull('l.business_id');
                    });
                });

            $leaveTypeNameExpression = DB::raw("'General' as leave_type_name");
            if (Schema::hasTable('leave_types')) {
                $recentPendingLeavesQuery->leftJoin('leave_types as lt', 'lt.id', '=', 'l.leave_type_id');

                if (Schema::hasColumn('leave_types', 'title')) {
                    $leaveTypeNameExpression = DB::raw("COALESCE(lt.title, 'General') as leave_type_name");
                } elseif (Schema::hasColumn('leave_types', 'name')) {
                    $leaveTypeNameExpression = DB::raw("COALESCE(lt.name, 'General') as leave_type_name");
                }
            }

            $recentPendingLeaves = $recentPendingLeavesQuery
                ->orderByDesc('l.created_at')
                ->limit(6)
                ->get([
                    'l.id',
                    'l.employee_id',
                    'l.start_date',
                    'l.end_date',
                    'l.days',
                    'l.created_at',
                    $leaveTypeNameExpression,
                    DB::raw("COALESCE(e.username, CONCAT_WS(' ', e.firstname, e.lastname)) as employee_name"),
                ]);
        }

        if (Schema::hasTable('attendances')) {
            $attendanceBase = DB::table('attendances')
                ->whereNull('deleted_at')
                ->whereDate('date', $today);
            $attendanceBase = $applyTenantScope($attendanceBase, 'attendances');

            $stats['today_attendance_records'] = (clone $attendanceBase)->count();
            $attendanceClockedEmployeeIds = (clone $attendanceBase)
                ->whereNotNull('clock_in')
                ->pluck('employee_id')
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values();

            $stats['today_clocked_in'] = $attendanceClockedEmployeeIds
                ->merge(collect($inferredClockedEmployeeIds))
                ->unique()
                ->count();
        }

        if (Schema::hasTable('hrm_payrolls')) {
            $payrollBase = DB::table('hrm_payrolls')->whereNull('deleted_at');
            $payrollBase = $applyTenantScope($payrollBase, 'hrm_payrolls');

            // Prefer detailed per-record period/net columns when available
            if (Schema::hasColumn('hrm_payrolls', 'period_start') && Schema::hasColumn('hrm_payrolls', 'net')) {
                $stats['this_month_payroll'] = (float) (clone $payrollBase)
                    ->whereBetween('period_start', [$monthStart, $monthEnd])
                    ->sum('net');

                $recentPayrollRuns = (clone $payrollBase)
                    ->orderByDesc('created_at')
                    ->limit(6)
                    ->get([
                        'id',
                        'employee_id',
                        'company_id',
                        'period_start',
                        'period_end',
                        'net',
                        'posted_to_accounts',
                        'created_at',
                    ]);
            }
            // Fallback to legacy aggregate columns (month/year + total_net)
            elseif (Schema::hasColumn('hrm_payrolls', 'month') && Schema::hasColumn('hrm_payrolls', 'year') && Schema::hasColumn('hrm_payrolls', 'total_net')) {
                $stats['this_month_payroll'] = (float) (clone $payrollBase)
                    ->where('month', (int) Carbon::now()->format('n'))
                    ->where('year', (int) Carbon::now()->format('Y'))
                    ->sum('total_net');

                $recentPayrollRuns = (clone $payrollBase)
                    ->orderByDesc('created_at')
                    ->limit(6)
                    ->get([
                        'id',
                        'employee_id',
                        'company_id',
                        'month',
                        'year',
                        'total_net as net',
                        'created_at',
                    ]);
            }
        }

        if (! Schema::hasTable('attendances') && ! empty($inferredClockedEmployeeIds)) {
            $stats['today_clocked_in'] = count(array_unique($inferredClockedEmployeeIds));
            $clockedInToday = $inferredClockByEmployeeId
                ->sortByDesc('clock_in_time')
                ->take(8)
                ->values();
        }

        if (Schema::hasTable('employees')) {
            $notClockedQuery = DB::table('employees as e')
                ->whereNull('e.deleted_at')
                ->whereNull('e.leaving_date');

            $notClockedQuery = $applyTenantScope($notClockedQuery, 'employees', 'e.business_id');

            if (Schema::hasTable('attendances')) {
                $notClockedQuery->whereNotExists(function ($subQ) use ($today, $businessId) {
                    $subQ->select(DB::raw(1))
                        ->from('attendances as a')
                        ->whereColumn('a.employee_id', 'e.id')
                        ->whereNull('a.deleted_at')
                        ->whereDate('a.date', $today)
                        ->whereNotNull('a.clock_in');

                    if ($businessId && Schema::hasColumn('attendances', 'business_id')) {
                        $subQ->where(function ($tenantQ) use ($businessId) {
                            $tenantQ->where('a.business_id', $businessId)
                                ->orWhereNull('a.business_id');
                        });
                    }
                });
            }

            if (! empty($inferredClockedEmployeeIds)) {
                $notClockedQuery->whereNotIn('e.id', $inferredClockedEmployeeIds);
            }

            if (Schema::hasTable('leaves')) {
                $notClockedQuery->whereNotExists(function ($subQ) use ($today, $businessId) {
                    $subQ->select(DB::raw(1))
                        ->from('leaves as l')
                        ->whereColumn('l.employee_id', 'e.id')
                        ->whereNull('l.deleted_at')
                        ->where('l.status', 'approved')
                        ->whereDate('l.start_date', '<=', $today)
                        ->whereDate('l.end_date', '>=', $today);

                    if ($businessId && Schema::hasColumn('leaves', 'business_id')) {
                        $subQ->where(function ($tenantQ) use ($businessId) {
                            $tenantQ->where('l.business_id', $businessId)
                                ->orWhereNull('l.business_id');
                        });
                    }
                });
            }

            $stats['absent_estimate_today'] = (clone $notClockedQuery)->count();

            $notClockedInToday = (clone $notClockedQuery)
                ->orderBy('e.firstname')
                ->limit($notClockedDisplayLimit)
                ->get([
                    'e.id',
                    'e.firstname',
                    'e.lastname',
                    'e.username',
                ]);

            $notClockedInTruncated = (int) $stats['absent_estimate_today'] > $notClockedInToday->count();

            if (Schema::hasTable('attendances')) {
                $clockedInQuery = DB::table('attendances as a')
                    ->join('employees as e', 'e.id', '=', 'a.employee_id')
                    ->whereNull('a.deleted_at')
                    ->whereNull('e.deleted_at')
                    ->whereDate('a.date', $today)
                    ->whereNotNull('a.clock_in');

                if ($businessId && Schema::hasColumn('attendances', 'business_id')) {
                    $clockedInQuery->where(function ($tenantQ) use ($businessId) {
                        $tenantQ->where('a.business_id', $businessId)
                            ->orWhereNull('a.business_id');
                    });
                }

                if ($businessId && Schema::hasColumn('employees', 'business_id')) {
                    $clockedInQuery->where(function ($tenantQ) use ($businessId) {
                        $tenantQ->where('e.business_id', $businessId)
                            ->orWhereNull('e.business_id');
                    });
                }

                $clockedInToday = $clockedInQuery
                    ->groupBy('e.id', 'e.firstname', 'e.lastname', 'e.username')
                    ->orderByRaw('MAX(a.clock_in) DESC')
                    ->limit(8)
                    ->get([
                        'e.id',
                        'e.firstname',
                        'e.lastname',
                        'e.username',
                        DB::raw('MAX(a.clock_in) as clock_in_time'),
                        DB::raw('MAX(a.clock_out) as clock_out_time'),
                    ]);

                $clockedInToday = $clockedInToday->map(function ($row) use ($today) {
                    if (! empty($row->clock_in_time)) {
                        $row->clock_in_time = Carbon::parse($today . ' ' . $row->clock_in_time)->format('d m Y H:i:s');
                    }

                    if (! empty($row->clock_out_time)) {
                        $row->clock_out_time = Carbon::parse($today . ' ' . $row->clock_out_time)->format('d m Y H:i:s');
                    }

                    return $row;
                });

                $alreadyListedIds = $clockedInToday->pluck('id')->map(fn($id) => (int) $id)->all();
                $inferredRows = $inferredClockByEmployeeId
                    ->reject(function ($row, $empId) use ($alreadyListedIds) {
                        return in_array((int) $empId, $alreadyListedIds, true);
                    })
                    ->sortByDesc('clock_in_time')
                    ->take(max(0, 8 - $clockedInToday->count()))
                    ->values();

                if ($inferredRows->isNotEmpty()) {
                    $clockedInToday = $clockedInToday->concat($inferredRows)->values();
                }
            }
        }

        if ((int) $stats['pending_leaves'] > 0) {
            $alerts[] = [
                'level' => 'warning',
                'message' => $stats['pending_leaves'] . ' leave request(s) are still pending approval.',
                'url' => action(['\\Modules\\Hrm\\Http\\Controllers\\LeaveController', 'index']),
                'cta' => 'Review leaves',
            ];
        }

        if ((int) $stats['absent_estimate_today'] > 0) {
            $alerts[] = [
                'level' => 'danger',
                'message' => $stats['absent_estimate_today'] . ' active employee(s) appear not clocked in today.',
                'url' => url('/hrm#not-clocked-in-list'),
                'cta' => 'View all',
            ];
        }

        return view('hrm::index', compact('stats', 'alerts', 'recentPendingLeaves', 'recentPayrollRuns', 'clockedInToday', 'notClockedInToday', 'notClockedInTruncated', 'notClockedDisplayLimit'));
    }
}
