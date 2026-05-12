@extends('layouts.app')

@section('title', __('ui.hrm_reports'))

@php
    $latestPeriod = !empty($stats['latest_period']) ? $stats['latest_period'] : __('ui.no_payroll_yet');
    $selectedEmployee = collect($employees ?? [])->firstWhere('id', (int) $selectedEmployeeId);
    $selectedEmployeeLabel = $selectedEmployee
        ? (trim(($selectedEmployee->firstname ?? '') . ' ' . ($selectedEmployee->lastname ?? '')) ?: ($selectedEmployee->username ?? 'Employee #' . $selectedEmployee->id))
        : null;
    $exportQuery = request()->query();
@endphp

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.hrm_reports'),
    'subtitle' => __('ui.central_reporting_for_payroll_payslips_p9_attendance_and_leave_analytics'),
    'actions' => '<a href="'.action([\Modules\Essentials\Http\Controllers\DashboardController::class, 'hrmDashboard']).'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.hrm_dashboard') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.report_filters') }}</h3>
        </div>
        <div class="box-body">
            <form method="GET" action="{{ route('hrm.reports.index') }}" class="row">
                <div class="col-md-3 col-sm-6 col-xs-12">
                    <div class="form-group">
                        <label>{{ __('ui.employee') }}</label>
                        <select name="employee_id" class="form-control">
                            <option value="">{{ __('ui.all_employees_2') }}</option>
                            @foreach($employees as $employee)
                                @php $employeeName = trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? '')) ?: ($employee->username ?? 'Employee #' . $employee->id); @endphp
                                <option value="{{ $employee->id }}" @selected($employee->id == $selectedEmployeeId)>{{ $employeeName }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 col-xs-12">
                    <div class="form-group">
                        <label>{{ __('ui.payroll_year') }}</label>
                        <select name="year" class="form-control">
                            @foreach($years as $year)
                                <option value="{{ $year }}" @selected($year == $selectedYear)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 col-xs-12">
                    <div class="form-group">
                        <label>{{ __('ui.attendance_start') }}</label>
                        <input type="date" name="attendance_start" value="{{ $attendanceStart }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 col-xs-12">
                    <div class="form-group">
                        <label>{{ __('ui.attendance_end') }}</label>
                        <input type="date" name="attendance_end" value="{{ $attendanceEnd }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 col-xs-12">
                    <div class="form-group">
                        <label>{{ __('ui.leave_start') }}</label>
                        <input type="date" name="leave_start" value="{{ $leaveStart }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-1 col-sm-6 col-xs-12">
                    <div class="form-group">
                        <label>{{ __('ui.leave_end') }}</label>
                        <input type="date" name="leave_end" value="{{ $leaveEnd }}" class="form-control">
                    </div>
                </div>
                <div class="col-xs-12">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> {{ __('ui.apply_filters') }}</button>
                    <a href="{{ route('hrm.reports.index') }}" class="btn btn-default">{{ __('ui.reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-navy hrm-kpi hrm-kpi-payroll">
                <div class="inner">
                    <h3>{{ number_format($stats['payroll_runs'] ?? 0) }}</h3>
                    <p>{{ __('Payroll Runs in :year', ['year' => $selectedYear]) }}</p>
                </div>
                <div class="icon"><i class="fa fa-calendar-check-o"></i></div>
                <a href="#payroll-summary-report" class="small-box-footer">{{ __('ui.payroll_summary') }} <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-green hrm-kpi hrm-kpi-finance">
                <div class="inner">
                    <h3>{{ number_format((float) ($stats['annual_net_total'] ?? 0), 2) }}</h3>
                    <p>{{ __('ui.annual_net_pay') }}</p>
                </div>
                <div class="icon"><i class="fa fa-money"></i></div>
                <a href="#recent-payroll-reports" class="small-box-footer">{{ __('ui.recent_payslips') }} <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-aqua hrm-kpi hrm-kpi-attendance">
                <div class="inner">
                    <h3>{{ $stats['attendance_total_work'] ?? '00:00' }}</h3>
                    <p>{{ __('ui.total_worked_in_attendance_range') }}</p>
                </div>
                <div class="icon"><i class="fa fa-clock-o"></i></div>
                <a href="#attendance-summary-report" class="small-box-footer">{{ __('ui.attendance_summary') }} <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-red hrm-kpi hrm-kpi-risk">
                <div class="inner">
                    <h3>{{ number_format((float) ($stats['leave_days'] ?? 0), 2) }}</h3>
                    <p>{{ __('ui.leave_days_in_leave_range') }}</p>
                </div>
                <div class="icon"><i class="fa fa-plane"></i></div>
                <a href="#leave-report" class="small-box-footer">{{ __('ui.leave_report') }} <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <div class="hrm-kpi-legend" aria-label="{{ __('ui.report_kpi_legend') }}">
                <span class="hrm-kpi-legend-title">{{ __('ui.kpi_colors') }}</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-payroll"></span> {{ __('ui.payroll_runs') }}</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-finance"></span> {{ __('ui.finance') }}</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-attendance"></span> {{ __('ui.attendance') }}</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-risk"></span> {{ __('ui.leave_risk') }}</span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 col-xs-12">
            <div class="box box-primary" id="payroll-summary-report">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('ui.payroll_summary_report') }}</h3>
                    <div class="box-tools pull-right">
                        <a href="{{ route('hrm.reports.export', array_merge(['section' => 'payroll', 'format' => 'xlsx'], $exportQuery)) }}" class="btn btn-xs btn-success">{{ __('ui.excel') }}</a>
                        <a href="{{ route('hrm.reports.export', array_merge(['section' => 'payroll', 'format' => 'pdf'], $exportQuery)) }}" class="btn btn-xs btn-default">{{ __('ui.pdf') }}</a>
                    </div>
                </div>
                <div class="box-body no-padding">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" style="margin-bottom:0;">
                            <thead>
                                <tr>
                                    <th>{{ __('ui.month') }}</th>
                                    <th class="text-right">{{ __('ui.runs') }}</th>
                                    <th class="text-right">{{ __('ui.employees_paid') }}</th>
                                    <th class="text-right">{{ __('ui.gross') }}</th>
                                    <th class="text-right">{{ __('ui.deductions') }}</th>
                                    <th class="text-right">{{ __('ui.paye') }}</th>
                                    <th class="text-right">{{ __('ui.net') }}</th>
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
                    <h3 class="box-title">{{ __('ui.quick_access') }}</h3>
                </div>
                <div class="box-body">
                    <a href="{{ route('hrm.payrolls.index') }}" class="btn btn-success btn-block"><i class="fa fa-money"></i> {{ __('ui.payroll_register') }}</a>
                    <a href="{{ route('hrm.employees.index') }}" class="btn btn-default btn-block"><i class="fa fa-users"></i> {{ __('ui.employees') }}</a>
                    <a href="{{ route('hrm.attendances.index') }}" class="btn btn-default btn-block"><i class="fa fa-clock-o"></i> {{ __('ui.attendance') }}</a>
                    <a href="{{ route('hrm.leaves.index') }}" class="btn btn-default btn-block"><i class="fa fa-plane"></i> {{ __('ui.leaves') }}</a>
                    <a href="{{ route('hrm.settings.index') }}" class="btn btn-default btn-block"><i class="fa fa-cog"></i> {{ __('ui.hrm_settings') }}</a>
                </div>
            </div>

            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('ui.current_scope') }}</h3>
                </div>
                <div class="box-body">
                    <p style="margin-bottom:6px;"><strong>{{ __('ui.employee_3') }}</strong> {{ $selectedEmployeeLabel ?: __('ui.all_employees_2') }}</p>
                    <p style="margin-bottom:6px;"><strong>{{ __('ui.payroll_year_2') }}</strong> {{ $selectedYear }}</p>
                    <p style="margin-bottom:6px;"><strong>{{ __('ui.attendance_2') }}</strong> {{ $attendanceStart }} {{ __('ui.to') }} {{ $attendanceEnd }}</p>
                    <p style="margin-bottom:0;"><strong>{{ __('ui.leave_2') }}</strong> {{ $leaveStart }} {{ __('ui.to') }} {{ $leaveEnd }}</p>
                </div>
            </div>

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('ui.p9_shortcut') }}</h3>
                </div>
                <div class="box-body">
                    <form method="GET" action="{{ route('hrm.payrolls.p9') }}">
                        <div class="form-group">
                            <label>{{ __('ui.employee') }}</label>
                            <select name="employee_id" class="form-control" required>
                                <option value="">{{ __('ui.select_employee') }}</option>
                                @foreach($employees as $employee)
                                    @php $employeeName = trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? '')) ?: ($employee->username ?? 'Employee #' . $employee->id); @endphp
                                    <option value="{{ $employee->id }}" @selected($employee->id == $selectedEmployeeId)>{{ $employeeName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ __('ui.year') }}</label>
                            <select name="year" class="form-control">
                                @foreach($years as $year)
                                    <option value="{{ $year }}" @selected($year == $selectedYear)>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-info btn-block"><i class="fa fa-file-text"></i> {{ __('ui.open_p9_form') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <div class="box box-warning" id="attendance-summary-report">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('ui.attendance_summary') }}</h3>
                    <div class="box-tools pull-right">
                        <a href="{{ route('hrm.reports.export', array_merge(['section' => 'attendance', 'format' => 'xlsx'], $exportQuery)) }}" class="btn btn-xs btn-success">{{ __('ui.excel') }}</a>
                        <a href="{{ route('hrm.reports.export', array_merge(['section' => 'attendance', 'format' => 'pdf'], $exportQuery)) }}" class="btn btn-xs btn-default">{{ __('ui.pdf') }}</a>
                    </div>
                </div>
                <div class="box-body no-padding">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" style="margin-bottom:0;">
                            <thead>
                                <tr>
                                    <th>{{ __('ui.employee') }}</th>
                                    <th class="text-right">{{ __('ui.records') }}</th>
                                    <th class="text-right">{{ __('ui.present_days') }}</th>
                                    <th class="text-right">{{ __('ui.total_work') }}</th>
                                    <th class="text-right">{{ __('ui.late_time') }}</th>
                                    <th class="text-right">{{ __('ui.overtime') }}</th>
                                    <th class="text-right">{{ __('ui.departed_early') }}</th>
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
                                        <td colspan="7" class="text-center text-muted">{{ __('ui.no_attendance_records_found_for_the_selected_range') }}</td>
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
                    <h3 class="box-title">{{ __('ui.leave_status_summary') }}</h3>
                </div>
                <div class="box-body no-padding">
                    <div class="table-responsive">
                        <table class="table table-striped" style="margin-bottom:0;">
                            <thead>
                                <tr>
                                    <th>{{ __('ui.status') }}</th>
                                    <th class="text-right">{{ __('ui.requests') }}</th>
                                    <th class="text-right">{{ __('ui.days_2') }}</th>
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
                                        <td colspan="3" class="text-center text-muted">{{ __('ui.no_leave_requests_in_the_selected_range') }}</td>
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
                    <h3 class="box-title">{{ __('ui.leave_report') }}</h3>
                    <div class="box-tools pull-right">
                        <a href="{{ route('hrm.reports.export', array_merge(['section' => 'leave', 'format' => 'xlsx'], $exportQuery)) }}" class="btn btn-xs btn-success">{{ __('ui.excel') }}</a>
                        <a href="{{ route('hrm.reports.export', array_merge(['section' => 'leave', 'format' => 'pdf'], $exportQuery)) }}" class="btn btn-xs btn-default">{{ __('ui.pdf') }}</a>
                    </div>
                </div>
                <div class="box-body no-padding">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" style="margin-bottom:0;">
                            <thead>
                                <tr>
                                    <th>{{ __('ui.employee') }}</th>
                                    <th>{{ __('ui.leave_type') }}</th>
                                    <th>{{ __('ui.start_date') }}</th>
                                    <th>{{ __('ui.end_date') }}</th>
                                    <th class="text-right">{{ __('ui.days_2') }}</th>
                                    <th>{{ __('ui.status') }}</th>
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
                                        <td colspan="6" class="text-center text-muted">{{ __('ui.no_leave_requests_found_for_the_selected_range') }}</td>
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
                    <h3 class="box-title">{{ __('ui.recent_payroll_reports') }}</h3>
                </div>
                <div class="box-body no-padding">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" style="margin-bottom:0;">
                            <thead>
                                <tr>
                                    <th>{{ __('ui.payroll_id') }}</th>
                                    <th>{{ __('ui.employee') }}</th>
                                    <th>{{ __('ui.period') }}</th>
                                    <th class="text-right">{{ __('ui.net_pay') }}</th>
                                    <th class="text-right">{{ __('ui.actions') }}</th>
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
                                            <a href="{{ route('hrm.payrolls.show', $payroll->id) }}" class="btn btn-xs btn-info">{{ __('ui.payslip') }}</a>
                                            <a href="{{ route('hrm.payrolls.p9', ['employee_id' => $payroll->employee_id, 'year' => $payroll->period_start ? \Carbon\Carbon::parse($payroll->period_start)->year : $selectedYear]) }}" class="btn btn-xs btn-default">{{ __('ui.p9') }}</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">{{ __('No payroll records found for :year.', ['year' => $selectedYear]) }}</td>
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