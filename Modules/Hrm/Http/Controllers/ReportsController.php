<?php

namespace Modules\Hrm\Http\Controllers;

use App\Business;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $payload = $this->buildReportPayload($request);

        return view('hrm::reports.index', $payload);
    }

    public function export(Request $request, string $section, string $format)
    {
        $payload = $this->buildReportPayload($request);
        $export = $this->buildSectionExport($payload, $section);

        if ($format === 'xlsx') {
            return Excel::download(
                new class($export['rows'], $export['headings']) implements FromCollection, WithHeadings {
                    public function __construct(private Collection $rows, private array $headings)
                    {
                    }

                    public function collection(): Collection
                    {
                        return $this->rows;
                    }

                    public function headings(): array
                    {
                        return $this->headings;
                    }
                },
                $export['filename'] . '.xlsx'
            );
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('hrm::reports.export_pdf', [
                'title' => $export['title'],
                'subtitle' => $export['subtitle'],
                'headings' => $export['headings'],
                'rows' => $export['rows'],
                'filters' => $export['filters'],
            ])->setPaper('a4', 'landscape');

            return $pdf->download($export['filename'] . '.pdf');
        }

        abort(404);
    }

    private function buildReportPayload(Request $request): array
    {
        $user = auth()->user();
        if (! $user || (! $user->can('hrm.access') && ! $user->can('hrm.payrolls'))) {
            abort(403);
        }

        $businessId = session('business.id');
        $selectedYear = (int) $request->get('year', now()->year);
        $selectedEmployeeId = $request->filled('employee_id') ? (int) $request->get('employee_id') : null;
        $attendanceStart = $this->normalizeDate($request->get('attendance_start'), now()->startOfMonth()->toDateString());
        $attendanceEnd = $this->normalizeDate($request->get('attendance_end'), now()->endOfMonth()->toDateString());
        $leaveStart = $this->normalizeDate($request->get('leave_start'), now()->startOfMonth()->toDateString());
        $leaveEnd = $this->normalizeDate($request->get('leave_end'), now()->endOfMonth()->toDateString());

        if ($attendanceStart > $attendanceEnd) {
            [$attendanceStart, $attendanceEnd] = [$attendanceEnd, $attendanceStart];
        }
        if ($leaveStart > $leaveEnd) {
            [$leaveStart, $leaveEnd] = [$leaveEnd, $leaveStart];
        }

        $companies = Schema::hasTable('business')
            ? Business::orderBy('name')->get(['id', 'name'])
            : collect([]);

        $employees = Schema::hasTable('employees')
            ? Employee::whereNull('deleted_at')
                ->when($businessId && Schema::hasColumn('employees', 'business_id'), function ($query) use ($businessId) {
                    $query->where(function ($tenantQuery) use ($businessId) {
                        $tenantQuery->where('business_id', $businessId)
                            ->orWhereNull('business_id');
                    });
                })
                ->orderBy('firstname')
                ->orderBy('lastname')
                ->get(['id', 'firstname', 'lastname', 'username'])
            : collect([]);

        $payrollSummaryRows = $this->buildPayrollSummaryRows($businessId, $selectedYear, $selectedEmployeeId);
        $attendanceSummaryRows = $this->buildAttendanceSummaryRows($businessId, $selectedEmployeeId, $attendanceStart, $attendanceEnd);
        $leaveRequestRows = $this->buildLeaveRequestRows($businessId, $selectedEmployeeId, $leaveStart, $leaveEnd);

        $stats = [
            'payroll_runs' => $payrollSummaryRows->sum('runs'),
            'annual_net_total' => (float) $payrollSummaryRows->sum('net'),
            'employees_on_payroll' => $payrollSummaryRows->sum('employees_paid'),
            'latest_period' => $payrollSummaryRows->whereNotNull('period_label')->last()['period_label'] ?? null,
            'attendance_records' => $attendanceSummaryRows->sum('records'),
            'attendance_total_work' => $this->minutesToTimeString((int) $attendanceSummaryRows->sum('total_work_minutes')),
            'leave_requests' => $leaveRequestRows->count(),
            'leave_days' => (float) $leaveRequestRows->sum('days'),
        ];

        $recentPayrolls = $this->buildRecentPayrollRows($businessId, $selectedYear, $selectedEmployeeId);
        $leaveStatusSummary = $leaveRequestRows
            ->groupBy(fn ($row) => strtolower((string) ($row['status'] ?? 'pending')))
            ->map(function (Collection $rows, string $status) {
                return [
                    'status' => ucfirst($status ?: 'Pending'),
                    'requests' => $rows->count(),
                    'days' => (float) $rows->sum('days'),
                ];
            })
            ->values();

        $years = range(now()->year, max(2020, now()->year - 5));

        return compact(
            'stats',
            'companies',
            'employees',
            'years',
            'selectedYear',
            'selectedEmployeeId',
            'attendanceStart',
            'attendanceEnd',
            'leaveStart',
            'leaveEnd',
            'recentPayrolls',
            'payrollSummaryRows',
            'attendanceSummaryRows',
            'leaveRequestRows',
            'leaveStatusSummary'
        );
    }

    private function buildPayrollSummaryRows(?int $businessId, int $selectedYear, ?int $selectedEmployeeId): Collection
    {
        $rows = collect([]);
        for ($month = 1; $month <= 12; $month++) {
            $rows->push([
                'month_number' => $month,
                'month' => Carbon::create()->month($month)->format('M'),
                'period_label' => null,
                'runs' => 0,
                'employees_paid' => 0,
                'gross' => 0.0,
                'deductions' => 0.0,
                'paye' => 0.0,
                'net' => 0.0,
            ]);
        }

        if (! Schema::hasTable('hrm_payrolls') || ! Schema::hasColumn('hrm_payrolls', 'period_start')) {
            return $rows;
        }

        $query = DB::table('hrm_payrolls')
            ->when(Schema::hasColumn('hrm_payrolls', 'deleted_at'), function ($builder) {
                $builder->whereNull('deleted_at');
            })
            ->when($businessId && Schema::hasColumn('hrm_payrolls', 'business_id'), function ($builder) use ($businessId) {
                $builder->where('business_id', $businessId);
            })
            ->whereYear('period_start', $selectedYear)
            ->when($selectedEmployeeId && Schema::hasColumn('hrm_payrolls', 'employee_id'), function ($builder) use ($selectedEmployeeId) {
                $builder->where('employee_id', $selectedEmployeeId);
            })
            ->get();

        $groupedEmployees = [];
        foreach ($query as $record) {
            $monthNumber = (int) Carbon::parse($record->period_start)->format('n');
            $index = $monthNumber - 1;
            $row = $rows[$index];
            $row['runs']++;
            $row['gross'] += (float) ($record->gross ?? 0);
            $row['deductions'] += (float) ($record->deductions ?? 0);
            $row['paye'] += (float) ($record->paye ?? 0);
            $row['net'] += (float) ($record->net ?? 0);
            $row['period_label'] = Carbon::parse($record->period_start)->format('M Y');

            if (isset($record->employee_id) && $record->employee_id) {
                $groupedEmployees[$monthNumber] = $groupedEmployees[$monthNumber] ?? [];
                $groupedEmployees[$monthNumber][$record->employee_id] = true;
            }

            $rows[$index] = $row;
        }

        return $rows->map(function (array $row) use ($groupedEmployees) {
            $row['employees_paid'] = count($groupedEmployees[$row['month_number']] ?? []);
            return $row;
        });
    }

    private function buildRecentPayrollRows(?int $businessId, int $selectedYear, ?int $selectedEmployeeId): Collection
    {
        if (! Schema::hasTable('hrm_payrolls')) {
            return collect([]);
        }

        return DB::table('hrm_payrolls as p')
            ->leftJoin('employees as e', 'p.employee_id', '=', 'e.id')
            ->when(Schema::hasColumn('hrm_payrolls', 'deleted_at'), function ($query) {
                $query->whereNull('p.deleted_at');
            })
            ->when($businessId && Schema::hasColumn('hrm_payrolls', 'business_id'), function ($query) use ($businessId) {
                $query->where('p.business_id', $businessId);
            })
            ->when(Schema::hasColumn('hrm_payrolls', 'period_start'), function ($query) use ($selectedYear) {
                $query->whereYear('p.period_start', $selectedYear);
            })
            ->when($selectedEmployeeId && Schema::hasColumn('hrm_payrolls', 'employee_id'), function ($query) use ($selectedEmployeeId) {
                $query->where('p.employee_id', $selectedEmployeeId);
            })
            ->select([
                'p.id',
                'p.employee_id',
                'p.period_start',
                'p.period_end',
                'p.net',
                DB::raw("COALESCE(e.username, CONCAT_WS(' ', e.firstname, e.lastname)) as employee_name"),
            ])
            ->orderByDesc('p.period_start')
            ->limit(8)
            ->get();
    }

    private function buildAttendanceSummaryRows(?int $businessId, ?int $selectedEmployeeId, string $attendanceStart, string $attendanceEnd): Collection
    {
        if (! Schema::hasTable('attendances') || ! Schema::hasColumn('attendances', 'date')) {
            return collect([]);
        }

        $hasStatus = Schema::hasColumn('attendances', 'status');
        $hasTotalWork = Schema::hasColumn('attendances', 'total_work');
        $hasLateTime = Schema::hasColumn('attendances', 'late_time');
        $hasOvertime = Schema::hasColumn('attendances', 'overtime');
        $hasDepartEarly = Schema::hasColumn('attendances', 'depart_early');

        $selectColumns = [
            'a.employee_id',
            'a.date',
            $hasStatus ? 'a.status' : DB::raw("'present' as status"),
            $hasTotalWork ? 'a.total_work' : DB::raw('NULL as total_work'),
            $hasLateTime ? 'a.late_time' : DB::raw('NULL as late_time'),
            $hasOvertime ? 'a.overtime' : DB::raw('NULL as overtime'),
            $hasDepartEarly ? 'a.depart_early' : DB::raw('NULL as depart_early'),
            DB::raw("COALESCE(e.username, CONCAT_WS(' ', e.firstname, e.lastname), CONCAT('Employee #', a.employee_id)) as employee_name"),
        ];

        $records = DB::table('attendances as a')
            ->leftJoin('employees as e', 'a.employee_id', '=', 'e.id')
            ->when(Schema::hasColumn('attendances', 'deleted_at'), function ($query) {
                $query->whereNull('a.deleted_at');
            })
            ->when($selectedEmployeeId, function ($query) use ($selectedEmployeeId) {
                $query->where('a.employee_id', $selectedEmployeeId);
            })
            ->when($businessId && Schema::hasColumn('attendances', 'business_id'), function ($query) use ($businessId) {
                $query->where('a.business_id', $businessId);
            }, function ($query) use ($businessId) {
                if ($businessId && Schema::hasColumn('employees', 'business_id')) {
                    $query->where(function ($tenantQuery) use ($businessId) {
                        $tenantQuery->where('e.business_id', $businessId)
                            ->orWhereNull('e.business_id');
                    });
                }
            })
            ->whereBetween('a.date', [$attendanceStart, $attendanceEnd])
            ->select($selectColumns)
            ->orderBy('employee_name')
            ->orderBy('a.date')
            ->get();

        $summary = [];
        foreach ($records as $record) {
            $key = (string) ($record->employee_id ?? $record->employee_name);
            if (! isset($summary[$key])) {
                $summary[$key] = [
                    'employee_id' => $record->employee_id,
                    'employee_name' => $record->employee_name,
                    'records' => 0,
                    'present_days' => 0,
                    'total_work_minutes' => 0,
                    'late_minutes' => 0,
                    'overtime_minutes' => 0,
                    'depart_early_minutes' => 0,
                ];
            }

            $summary[$key]['records']++;
            if (strtolower((string) ($record->status ?? 'present')) === 'present') {
                $summary[$key]['present_days']++;
            }
            $summary[$key]['total_work_minutes'] += $this->timeStringToMinutes($record->total_work ?? null);
            $summary[$key]['late_minutes'] += $this->timeStringToMinutes($record->late_time ?? null);
            $summary[$key]['overtime_minutes'] += $this->timeStringToMinutes($record->overtime ?? null);
            $summary[$key]['depart_early_minutes'] += $this->timeStringToMinutes($record->depart_early ?? null);
        }

        return collect(array_values($summary))
            ->map(function (array $row) {
                $row['total_work'] = $this->minutesToTimeString($row['total_work_minutes']);
                $row['late_time'] = $this->minutesToTimeString($row['late_minutes']);
                $row['overtime'] = $this->minutesToTimeString($row['overtime_minutes']);
                $row['depart_early'] = $this->minutesToTimeString($row['depart_early_minutes']);
                return $row;
            })
            ->sortByDesc('present_days')
            ->values();
    }

    private function buildLeaveRequestRows(?int $businessId, ?int $selectedEmployeeId, string $leaveStart, string $leaveEnd): Collection
    {
        if (! Schema::hasTable('leaves')) {
            return collect([]);
        }

        $leaveTypeExpression = "'General' as leave_type_name";
        if (Schema::hasTable('leave_types')) {
            if (Schema::hasColumn('leave_types', 'title')) {
                $leaveTypeExpression = "COALESCE(lt.title, 'General') as leave_type_name";
            } elseif (Schema::hasColumn('leave_types', 'name')) {
                $leaveTypeExpression = "COALESCE(lt.name, 'General') as leave_type_name";
            }
        }

        $query = DB::table('leaves as l')
            ->leftJoin('employees as e', 'l.employee_id', '=', 'e.id')
            ->when(Schema::hasTable('leave_types'), function ($builder) {
                $builder->leftJoin('leave_types as lt', 'l.leave_type_id', '=', 'lt.id');
            })
            ->when(Schema::hasColumn('leaves', 'deleted_at'), function ($builder) {
                $builder->whereNull('l.deleted_at');
            })
            ->when($selectedEmployeeId, function ($builder) use ($selectedEmployeeId) {
                $builder->where('l.employee_id', $selectedEmployeeId);
            })
            ->when($businessId && Schema::hasColumn('leaves', 'business_id'), function ($builder) use ($businessId) {
                $builder->where('l.business_id', $businessId);
            }, function ($builder) use ($businessId) {
                if ($businessId && Schema::hasColumn('employees', 'business_id')) {
                    $builder->where(function ($tenantQuery) use ($businessId) {
                        $tenantQuery->where('e.business_id', $businessId)
                            ->orWhereNull('e.business_id');
                    });
                }
            })
            ->whereDate('l.start_date', '<=', $leaveEnd)
            ->whereDate('l.end_date', '>=', $leaveStart)
            ->select([
                'l.id',
                'l.employee_id',
                'l.start_date',
                'l.end_date',
                'l.days',
                'l.status',
                DB::raw("COALESCE(e.username, CONCAT_WS(' ', e.firstname, e.lastname), CONCAT('Employee #', l.employee_id)) as employee_name"),
                DB::raw($leaveTypeExpression),
            ])
            ->orderByDesc('l.start_date');

        return $query->limit(50)->get()->map(function ($row) {
            return [
                'id' => $row->id,
                'employee_id' => $row->employee_id,
                'employee_name' => $row->employee_name,
                'leave_type_name' => $row->leave_type_name,
                'start_date' => $row->start_date,
                'end_date' => $row->end_date,
                'days' => (float) ($row->days ?? 0),
                'status' => ucfirst((string) ($row->status ?? 'Pending')),
            ];
        });
    }

    private function buildSectionExport(array $payload, string $section): array
    {
        $selectedEmployee = collect($payload['employees'])->firstWhere('id', (int) $payload['selectedEmployeeId']);
        $employeeLabel = $selectedEmployee
            ? (trim(($selectedEmployee->firstname ?? '') . ' ' . ($selectedEmployee->lastname ?? '')) ?: ($selectedEmployee->username ?? 'Employee #' . $selectedEmployee->id))
            : 'All employees';

        if ($section === 'payroll') {
            return [
                'title' => 'Payroll Summary Report',
                'subtitle' => 'Monthly payroll totals for ' . $payload['selectedYear'],
                'filename' => 'hrm-payroll-summary-' . $payload['selectedYear'],
                'headings' => ['Month', 'Runs', 'Employees Paid', 'Gross Pay', 'Deductions', 'PAYE', 'Net Pay'],
                'rows' => $payload['payrollSummaryRows']->map(function ($row) {
                    return [
                        $row['month'],
                        $row['runs'],
                        $row['employees_paid'],
                        number_format((float) $row['gross'], 2, '.', ''),
                        number_format((float) $row['deductions'], 2, '.', ''),
                        number_format((float) $row['paye'], 2, '.', ''),
                        number_format((float) $row['net'], 2, '.', ''),
                    ];
                }),
                'filters' => [
                    'Year' => $payload['selectedYear'],
                    'Employee' => $employeeLabel,
                ],
            ];
        }

        if ($section === 'attendance') {
            return [
                'title' => 'Attendance Summary Report',
                'subtitle' => 'Attendance summary from ' . $payload['attendanceStart'] . ' to ' . $payload['attendanceEnd'],
                'filename' => 'hrm-attendance-summary-' . $payload['attendanceStart'] . '-to-' . $payload['attendanceEnd'],
                'headings' => ['Employee', 'Records', 'Present Days', 'Total Work', 'Late Time', 'Overtime', 'Departed Early'],
                'rows' => $payload['attendanceSummaryRows']->map(function ($row) {
                    return [
                        $row['employee_name'],
                        $row['records'],
                        $row['present_days'],
                        $row['total_work'],
                        $row['late_time'],
                        $row['overtime'],
                        $row['depart_early'],
                    ];
                }),
                'filters' => [
                    'Employee' => $employeeLabel,
                    'Attendance Start' => $payload['attendanceStart'],
                    'Attendance End' => $payload['attendanceEnd'],
                ],
            ];
        }

        if ($section === 'leave') {
            return [
                'title' => 'Leave Requests Report',
                'subtitle' => 'Leave requests overlapping ' . $payload['leaveStart'] . ' to ' . $payload['leaveEnd'],
                'filename' => 'hrm-leave-report-' . $payload['leaveStart'] . '-to-' . $payload['leaveEnd'],
                'headings' => ['Employee', 'Leave Type', 'Start Date', 'End Date', 'Days', 'Status'],
                'rows' => $payload['leaveRequestRows']->map(function ($row) {
                    return [
                        $row['employee_name'],
                        $row['leave_type_name'],
                        $row['start_date'],
                        $row['end_date'],
                        number_format((float) $row['days'], 2, '.', ''),
                        $row['status'],
                    ];
                }),
                'filters' => [
                    'Employee' => $employeeLabel,
                    'Leave Start' => $payload['leaveStart'],
                    'Leave End' => $payload['leaveEnd'],
                ],
            ];
        }

        abort(404);
    }

    private function normalizeDate(?string $date, string $fallback): string
    {
        try {
            return $date ? Carbon::parse($date)->toDateString() : $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    private function timeStringToMinutes(?string $value): int
    {
        if (empty($value) || ! str_contains((string) $value, ':')) {
            return 0;
        }

        [$hours, $minutes] = array_pad(explode(':', (string) $value), 2, 0);
        return ((int) $hours * 60) + (int) $minutes;
    }

    private function minutesToTimeString(int $minutes): string
    {
        $hours = intdiv(max($minutes, 0), 60);
        $mins = max($minutes, 0) % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }
}