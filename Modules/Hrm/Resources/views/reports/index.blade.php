@extends('layouts.app')

@section('title', 'HRM Reports')

@php
    $latestPeriod = !empty($stats['latest_period']) ? $stats['latest_period'] : 'No payroll yet';
    $selectedEmployee = collect($employees ?? [])->firstWhere('id', (int) $selectedEmployeeId);
    $selectedEmployeeLabel = $selectedEmployee
        ? (trim(($selectedEmployee->firstname ?? '') . ' ' . ($selectedEmployee->lastname ?? '')) ?: ($selectedEmployee->username ?? 'Employee #' . $selectedEmployee->id))
        : null;
    $exportQuery = request()->query();
@endphp

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'HRM Reports',
    'subtitle' => 'Central reporting for payroll, payslips, P9, attendance, and leave analytics.',
    'actions' => '<a href="'.action([\Modules\Hrm\Http\Controllers\HrmController::class, 'index']).'" class="btn btn-default"><i class="fa fa-arrow-left"></i> HRM Dashboard</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Report Filters</h3>
        </div>
        <div class="box-body">
            <form method="GET" action="{{ route('hrm.reports.index') }}" class="row">
                <div class="col-md-3 col-sm-6 col-xs-12">
                    <div class="form-group">
                        <label>Employee</label>
                        <select name="employee_id" class="form-control">
                            <option value="">All employees</option>
                            @foreach($employees as $employee)
                                @php $employeeName = trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? '')) ?: ($employee->username ?? 'Employee #' . $employee->id); @endphp
                                <option value="{{ $employee->id }}" @selected($employee->id == $selectedEmployeeId)>{{ $employeeName }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 col-xs-12">
                    <div class="form-group">
                        <label>Payroll Year</label>
                        <select name="year" class="form-control">
                            @foreach($years as $year)
                                <option value="{{ $year }}" @selected($year == $selectedYear)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 col-xs-12">
                    <div class="form-group">
                        <label>Attendance Start</label>
                        <input type="date" name="attendance_start" value="{{ $attendanceStart }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 col-xs-12">
                    <div class="form-group">
                        <label>Attendance End</label>
                        <input type="date" name="attendance_end" value="{{ $attendanceEnd }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 col-xs-12">
                    <div class="form-group">
                        <label>Leave Start</label>
                        <input type="date" name="leave_start" value="{{ $leaveStart }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-1 col-sm-6 col-xs-12">
                    <div class="form-group">
                        <label>Leave End</label>
                        <input type="date" name="leave_end" value="{{ $leaveEnd }}" class="form-control">
                    </div>
                </div>
                <div class="col-xs-12">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Apply Filters</button>
                    <a href="{{ route('hrm.reports.index') }}" class="btn btn-default">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-navy hrm-kpi hrm-kpi-payroll">
                <div class="inner">
                    <h3>{{ number_format($stats['payroll_runs'] ?? 0) }}</h3>
                    <p>Payroll Runs in {{ $selectedYear }}</p>
                </div>
                <div class="icon"><i class="fa fa-calendar-check-o"></i></div>
                <a href="#payroll-summary-report" class="small-box-footer">Payroll Summary <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-green hrm-kpi hrm-kpi-finance">
                <div class="inner">
                    <h3>{{ number_format((float) ($stats['annual_net_total'] ?? 0), 2) }}</h3>
                    <p>Annual Net Pay</p>
                </div>
                <div class="icon"><i class="fa fa-money"></i></div>
                <a href="#recent-payroll-reports" class="small-box-footer">Recent Payslips <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-aqua hrm-kpi hrm-kpi-attendance">
                <div class="inner">
                    <h3>{{ $stats['attendance_total_work'] ?? '00:00' }}</h3>
                    <p>Total Worked in Attendance Range</p>
                </div>
                <div class="icon"><i class="fa fa-clock-o"></i></div>
                <a href="#attendance-summary-report" class="small-box-footer">Attendance Summary <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-red hrm-kpi hrm-kpi-risk">
                <div class="inner">
                    <h3>{{ number_format((float) ($stats['leave_days'] ?? 0), 2) }}</h3>
                    <p>Leave Days in Leave Range</p>
                </div>
                <div class="icon"><i class="fa fa-plane"></i></div>
                <a href="#leave-report" class="small-box-footer">Leave Report <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <div class="hrm-kpi-legend" aria-label="Report KPI legend">
                <span class="hrm-kpi-legend-title">KPI Colors:</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-payroll"></span> Payroll Runs</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-finance"></span> Finance</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-attendance"></span> Attendance</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-risk"></span> Leave Risk</span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 col-xs-12">
            <div class="box box-primary" id="payroll-summary-report">
                <div class="box-header with-border">
                    <h3 class="box-title">Payroll Summary Report</h3>
                    <div class="box-tools pull-right">
                        <a href="{{ route('hrm.reports.export', array_merge(['section' => 'payroll', 'format' => 'xlsx'], $exportQuery)) }}" class="btn btn-xs btn-success">Excel</a>
                        <a href="{{ route('hrm.reports.export', array_merge(['section' => 'payroll', 'format' => 'pdf'], $exportQuery)) }}" class="btn btn-xs btn-default">PDF</a>
                    </div>
                </div>
                <div class="box-body no-padding">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" style="margin-bottom:0;">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th class="text-right">Runs</th>
                                    <th class="text-right">Employees Paid</th>
                                    <th class="text-right">Gross</th>
                                    <th class="text-right">Deductions</th>
                                    <th class="text-right">PAYE</th>
                                    <th class="text-right">Net</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payrollSummaryRows as $row)
                                    <tr>
                                        <td>{{ $row['month'] }}</td>
                                        <td class="text-right">{{ number_format($row['runs']) }}</td>
                                        <td class="text-right">{{ number_format($row['employees_paid']) }}</td>
                                        <td class="text-right">{{ number_format((float) $row['gross'], 2) }}</td>
                                        <td class="text-right">{{ number_format((float) $row['deductions'], 2) }}</td>
                                        <td class="text-right">{{ number_format((float) $row['paye'], 2) }}</td>
                                        <td class="text-right"><strong>{{ number_format((float) $row['net'], 2) }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xs-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">Quick Access</h3>
                </div>
                <div class="box-body">
                    <a href="{{ route('hrm.payrolls.index') }}" class="btn btn-success btn-block"><i class="fa fa-money"></i> Payroll Register</a>
                    <a href="{{ route('hrm.employees.index') }}" class="btn btn-default btn-block"><i class="fa fa-users"></i> Employees</a>
                    <a href="{{ route('hrm.attendances.index') }}" class="btn btn-default btn-block"><i class="fa fa-clock-o"></i> Attendance</a>
                    <a href="{{ route('hrm.leaves.index') }}" class="btn btn-default btn-block"><i class="fa fa-plane"></i> Leaves</a>
                    <a href="{{ route('hrm.settings.index') }}" class="btn btn-default btn-block"><i class="fa fa-cog"></i> HRM Settings</a>
                </div>
            </div>

            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Current Scope</h3>
                </div>
                <div class="box-body">
                    <p style="margin-bottom:6px;"><strong>Employee:</strong> {{ $selectedEmployeeLabel ?: 'All employees' }}</p>
                    <p style="margin-bottom:6px;"><strong>Payroll year:</strong> {{ $selectedYear }}</p>
                    <p style="margin-bottom:6px;"><strong>Attendance:</strong> {{ $attendanceStart }} to {{ $attendanceEnd }}</p>
                    <p style="margin-bottom:0;"><strong>Leave:</strong> {{ $leaveStart }} to {{ $leaveEnd }}</p>
                </div>
            </div>

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">P9 Shortcut</h3>
                </div>
                <div class="box-body">
                    <form method="GET" action="{{ route('hrm.payrolls.p9') }}">
                        <div class="form-group">
                            <label>Employee</label>
                            <select name="employee_id" class="form-control" required>
                                <option value="">-- Select Employee --</option>
                                @foreach($employees as $employee)
                                    @php $employeeName = trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? '')) ?: ($employee->username ?? 'Employee #' . $employee->id); @endphp
                                    <option value="{{ $employee->id }}" @selected($employee->id == $selectedEmployeeId)>{{ $employeeName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Year</label>
                            <select name="year" class="form-control">
                                @foreach($years as $year)
                                    <option value="{{ $year }}" @selected($year == $selectedYear)>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-info btn-block"><i class="fa fa-file-text"></i> Open P9 Form</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <div class="box box-warning" id="attendance-summary-report">
                <div class="box-header with-border">
                    <h3 class="box-title">Attendance Summary</h3>
                    <div class="box-tools pull-right">
                        <a href="{{ route('hrm.reports.export', array_merge(['section' => 'attendance', 'format' => 'xlsx'], $exportQuery)) }}" class="btn btn-xs btn-success">Excel</a>
                        <a href="{{ route('hrm.reports.export', array_merge(['section' => 'attendance', 'format' => 'pdf'], $exportQuery)) }}" class="btn btn-xs btn-default">PDF</a>
                    </div>
                </div>
                <div class="box-body no-padding">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" style="margin-bottom:0;">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th class="text-right">Records</th>
                                    <th class="text-right">Present Days</th>
                                    <th class="text-right">Total Work</th>
                                    <th class="text-right">Late Time</th>
                                    <th class="text-right">Overtime</th>
                                    <th class="text-right">Departed Early</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendanceSummaryRows as $row)
                                    <tr>
                                        <td>{{ $row['employee_name'] }}</td>
                                        <td class="text-right">{{ number_format($row['records']) }}</td>
                                        <td class="text-right">{{ number_format($row['present_days']) }}</td>
                                        <td class="text-right">{{ $row['total_work'] }}</td>
                                        <td class="text-right">{{ $row['late_time'] }}</td>
                                        <td class="text-right">{{ $row['overtime'] }}</td>
                                        <td class="text-right">{{ $row['depart_early'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No attendance records found for the selected range.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 col-xs-12">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">Leave Status Summary</h3>
                </div>
                <div class="box-body no-padding">
                    <div class="table-responsive">
                        <table class="table table-striped" style="margin-bottom:0;">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th class="text-right">Requests</th>
                                    <th class="text-right">Days</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($leaveStatusSummary as $statusRow)
                                    <tr>
                                        <td>{{ $statusRow['status'] }}</td>
                                        <td class="text-right">{{ number_format($statusRow['requests']) }}</td>
                                        <td class="text-right">{{ number_format((float) $statusRow['days'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No leave requests in the selected range.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8 col-xs-12">
            <div class="box box-danger" id="leave-report">
                <div class="box-header with-border">
                    <h3 class="box-title">Leave Report</h3>
                    <div class="box-tools pull-right">
                        <a href="{{ route('hrm.reports.export', array_merge(['section' => 'leave', 'format' => 'xlsx'], $exportQuery)) }}" class="btn btn-xs btn-success">Excel</a>
                        <a href="{{ route('hrm.reports.export', array_merge(['section' => 'leave', 'format' => 'pdf'], $exportQuery)) }}" class="btn btn-xs btn-default">PDF</a>
                    </div>
                </div>
                <div class="box-body no-padding">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" style="margin-bottom:0;">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Leave Type</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th class="text-right">Days</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($leaveRequestRows as $row)
                                    <tr>
                                        <td>{{ $row['employee_name'] }}</td>
                                        <td>{{ $row['leave_type_name'] }}</td>
                                        <td>{{ $row['start_date'] }}</td>
                                        <td>{{ $row['end_date'] }}</td>
                                        <td class="text-right">{{ number_format((float) $row['days'], 2) }}</td>
                                        <td>{{ $row['status'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No leave requests found for the selected range.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="recent-payroll-reports">
        <div class="col-xs-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">Recent Payroll Reports</h3>
                </div>
                <div class="box-body no-padding">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" style="margin-bottom:0;">
                            <thead>
                                <tr>
                                    <th>Payroll ID</th>
                                    <th>Employee</th>
                                    <th>Period</th>
                                    <th class="text-right">Net Pay</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPayrolls as $payroll)
                                    <tr>
                                        <td>#{{ $payroll->id }}</td>
                                        <td>{{ $payroll->employee_name ?: ('Employee #' . $payroll->employee_id) }}</td>
                                        <td>
                                            {{ $payroll->period_start ? \Carbon\Carbon::parse($payroll->period_start)->format('d M Y') : '-' }} -
                                            {{ $payroll->period_end ? \Carbon\Carbon::parse($payroll->period_end)->format('d M Y') : '-' }}
                                        </td>
                                        <td class="text-right">{{ number_format((float) ($payroll->net ?? 0), 2) }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('hrm.payrolls.show', $payroll->id) }}" class="btn btn-xs btn-info">Payslip</a>
                                            <a href="{{ route('hrm.payrolls.p9', ['employee_id' => $payroll->employee_id, 'year' => $payroll->period_start ? \Carbon\Carbon::parse($payroll->period_start)->year : $selectedYear]) }}" class="btn btn-xs btn-default">P9</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No payroll records found for {{ $selectedYear }}.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection