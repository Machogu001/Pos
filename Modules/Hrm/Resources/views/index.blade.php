@extends('layouts.app')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('Human Resource Management'),
    'subtitle' => __('Operational dashboard'),
    'actions' => '<a href="'.route('hrm.reports.index').'" class="btn btn-default"><i class="fa fa-bar-chart"></i> '. __('Open Reports') .'</a>'
])

<section class="content">
    @if(!empty($alerts))
        <div class="row">
            <div class="col-xs-12">
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h2 class="box-title h3">{{ __('Attention Needed') }}</h2>
                    </div>
                    <div class="box-body" style="padding-bottom: 5px;">
                        @foreach($alerts as $alert)
                            <div class="alert alert-{{ $alert['level'] === 'danger' ? 'danger' : 'warning' }}" style="margin-bottom: 10px;">
                                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                                    <span>{{ $alert['message'] }}</span>
                                    <a href="{{ $alert['url'] }}" class="btn btn-xs btn-{{ $alert['level'] === 'danger' ? 'danger' : 'warning' }}">{{ $alert['cta'] }}</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-aqua hrm-kpi hrm-kpi-people">
                <div class="inner">
                    <h3>{{ number_format($stats['total_employees'] ?? 0) }}</h3>
                    <p>{{ __('Total Employees') }}</p>
                </div>
                <div class="icon">
                    <i class="fa fa-users"></i>
                </div>
                <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\EmployeesController', 'index']) }}" class="small-box-footer">
                    {{ __('Manage Employees') }} <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-green hrm-kpi hrm-kpi-active">
                <div class="inner">
                    <h3>{{ number_format($stats['active_employees'] ?? 0) }}</h3>
                    <p>{{ __('Active Employees') }}</p>
                </div>
                <div class="icon">
                    <i class="fa fa-user"></i>
                </div>
                <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\EmployeesController', 'index']) }}" class="small-box-footer">
                    {{ __('View Active Staff') }} <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-yellow hrm-kpi hrm-kpi-leave">
                <div class="inner">
                    <h3>{{ number_format($stats['on_leave_today'] ?? 0) }}</h3>
                    <p>{{ __('On Leave Today') }}</p>
                </div>
                <div class="icon">
                    <i class="fa fa-plane"></i>
                </div>
                <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\LeaveController', 'index']) }}" class="small-box-footer">
                    {{ __('View Leave Calendar') }} <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-red hrm-kpi hrm-kpi-risk">
                <div class="inner">
                    <h3>{{ number_format($stats['pending_leaves'] ?? 0) }}</h3>
                    <p>{{ __('Pending Leave Requests') }}</p>
                </div>
                <div class="icon">
                    <i class="fa fa-clock-o"></i>
                </div>
                <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\LeaveController', 'index']) }}" class="small-box-footer">
                    {{ __('Review Requests') }} <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-navy hrm-kpi hrm-kpi-payroll">
                <div class="inner">
                    <h3>
                        {{ number_format($stats['this_month_payroll'] ?? 0, 2) }}
                    </h3>
                    <p>{{ __('This Month Payroll (Net)') }}</p>
                </div>
                <div class="icon">
                    <i class="fa fa-money"></i>
                </div>
                <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\PayrollController', 'index']) }}" class="small-box-footer">
                    {{ __('Open Payroll') }} <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-teal hrm-kpi hrm-kpi-attendance">
                <div class="inner">
                    <h3>{{ number_format($stats['today_clocked_in'] ?? 0) }}</h3>
                    <p>{{ __('Clocked In Today') }}</p>
                </div>
                <div class="icon">
                    <i class="fa fa-sign-in"></i>
                </div>
                <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\AttendancesController', 'index']) }}" class="small-box-footer">
                    {{ __('Attendance Sheet') }} <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-orange hrm-kpi hrm-kpi-attention">
                <div class="inner">
                    <h3>{{ number_format($stats['absent_estimate_today'] ?? 0) }}</h3>
                    <p>{{ __('Estimated Not Clocked In') }}</p>
                </div>
                <div class="icon">
                    <i class="fa fa-user-times"></i>
                </div>
                <a href="#clocked-in-list" class="small-box-footer">
                    {{ __('View Clocked In') }} <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-maroon hrm-kpi hrm-kpi-structure">
                <div class="inner">
                    <h3>{{ number_format($stats['total_departments'] ?? 0) }}</h3>
                    <p>{{ __('Departments Configured') }}</p>
                </div>
                <div class="icon">
                    <i class="fa fa-sitemap"></i>
                </div>
                <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\DepartmentsController', 'index']) }}" class="small-box-footer">
                    {{ __('Open Departments') }} <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <div class="hrm-kpi-legend" aria-label="KPI legend">
                <span class="hrm-kpi-legend-title">KPI Colors:</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-people"></span> People</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-active"></span> Active</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-leave"></span> Leave</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-risk"></span> Risk</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-payroll"></span> Payroll</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-attendance"></span> Attendance</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-attention"></span> Attention</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-structure"></span> Structure</span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 col-xs-12">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h2 class="box-title h3">{{ __('Recent Pending Leave Requests') }}</h2>
                    <div class="box-tools pull-right">
                        <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\LeaveController', 'index']) }}" class="btn btn-xs btn-default">{{ __('View all') }}</a>
                    </div>
                </div>
                <div class="box-body no-padding">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" style="margin-bottom:0;" aria-label="{{ __('Recent pending leave requests') }}">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Type</th>
                                    <th>Period</th>
                                    <th>Days</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPendingLeaves as $leave)
                                    <tr>
                                        <td>{{ $leave->employee_name ?: 'Employee #'.$leave->employee_id }}</td>
                                        <td>{{ $leave->leave_type_name ?: 'General' }}</td>
                                        <td>{{ $leave->start_date }} to {{ $leave->end_date }}</td>
                                        <td>{{ $leave->days }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No pending leave requests.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xs-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h2 class="box-title h3">{{ __('Recent Payroll Runs') }}</h2>
                    <div class="box-tools pull-right">
                        <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\PayrollController', 'index']) }}" class="btn btn-xs btn-default">{{ __('View all') }}</a>
                    </div>
                </div>
                <div class="box-body no-padding">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" style="margin-bottom:0;" aria-label="{{ __('Recent payroll runs') }}">
                            <thead>
                                <tr>
                                    <th>Run ID</th>
                                    <th>Period</th>
                                    <th class="text-right">Net</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPayrollRuns as $run)
                                    <tr>
                                        <td>#{{ $run->id }}</td>
                                        <td>
                                            @if(isset($run->period_start) && isset($run->period_end))
                                                {{ $run->period_start }} to {{ $run->period_end }}
                                            @else
                                                {{ ($run->month ?? '-') }}/{{ ($run->year ?? '-') }}
                                            @endif
                                        </td>
                                        <td class="text-right">{{ number_format((float) ($run->net ?? 0), 2) }}</td>
                                        <td>
                                            @if(isset($run->posted_to_accounts))
                                                @if((int) $run->posted_to_accounts === 1)
                                                    <span class="label label-success">Posted</span>
                                                @else
                                                    <span class="label label-default">Saved</span>
                                                @endif
                                            @else
                                                <span class="label label-default">Saved</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No payroll runs yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="clocked-in-list">
        <div class="col-xs-12">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h2 class="box-title h3">{{ __('Clocked In Today') }}</h2>
                    <div class="box-tools pull-right">
                        <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\AttendancesController', 'index']) }}" class="btn btn-xs btn-default">{{ __('Open Attendance') }}</a>
                    </div>
                </div>
                <div class="box-body no-padding">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" style="margin-bottom:0;" aria-label="{{ __('Clocked in employees today') }}">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Username</th>
                                    <th>Clock In</th>
                                    <th>Clock Out</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($clockedInToday ?? []) as $emp)
                                    <tr>
                                        <td>{{ trim(($emp->firstname ?? '').' '.($emp->lastname ?? '')) ?: ('Employee #'.$emp->id) }}</td>
                                        <td>{{ $emp->username ?: '-' }}</td>
                                        <td>{{ $emp->clock_in_time ?: '-' }}</td>
                                        <td>{{ $emp->clock_out_time ?: '-' }}</td>
                                        <td class="text-right">
                                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\EmployeesController', 'edit'], ['employee' => $emp->id]) }}" class="btn btn-xs btn-default">Open Profile</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No employees have clocked in today.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="not-clocked-in-list">
        <div class="col-xs-12">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h2 class="box-title h3">{{ __('Not Clocked In Today (All)') }}</h2>
                    <div class="box-tools pull-right">
                        <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\AttendancesController', 'index']) }}" class="btn btn-xs btn-default">{{ __('Open Attendance') }}</a>
                    </div>
                </div>
                <div class="box-body no-padding">
                    @if(!empty($notClockedInTruncated))
                        <div class="alert alert-info" style="margin:10px;">
                            {{ __('Showing first :limit records for performance. Refine attendance review for full detail.', ['limit' => $notClockedDisplayLimit ?? 500]) }}
                        </div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" style="margin-bottom:0;" aria-label="{{ __('Employees not clocked in today') }}">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Username</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($notClockedInToday ?? []) as $emp)
                                    <tr>
                                        <td>{{ trim(($emp->firstname ?? '').' '.($emp->lastname ?? '')) ?: ('Employee #'.$emp->id) }}</td>
                                        <td>{{ $emp->username ?: '-' }}</td>
                                        <td class="text-right">
                                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\EmployeesController', 'edit'], ['employee' => $emp->id]) }}" class="btn btn-xs btn-default">Open Profile</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Everyone is either clocked in or on approved leave today.</td>
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
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">HRM Quick Links</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-3 col-sm-4 col-xs-6">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\CompanyController', 'index']) }}" class="btn btn-default btn-block">
                                <i class="fa fa-building"></i> Companies
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\DepartmentsController', 'index']) }}" class="btn btn-default btn-block">
                                <i class="fa fa-sitemap"></i> Departments
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\DesignationsController', 'index']) }}" class="btn btn-default btn-block">
                                <i class="fa fa-id-badge"></i> Designations
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\OfficeShiftController', 'index']) }}" class="btn btn-default btn-block">
                                <i class="fa fa-calendar"></i> Office Shifts
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-top:10px;">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\AttendancesController', 'index']) }}" class="btn btn-default btn-block">
                                <i class="fa fa-clock-o"></i> Attendance
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-top:10px;">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\HolidayController', 'index']) }}" class="btn btn-default btn-block">
                                <i class="fa fa-calendar-times-o"></i> Holidays
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-top:10px;">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\EmployeesController', 'index']) }}" class="btn btn-primary btn-block">
                                <i class="fa fa-users"></i> Employees
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-top:10px;">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\LeaveController', 'index']) }}" class="btn btn-primary btn-block">
                                <i class="fa fa-plane"></i> Leaves
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-top:10px;">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\LeaveTypeController', 'index']) }}" class="btn btn-default btn-block">
                                <i class="fa fa-tags"></i> Leave Types
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-top:10px;">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\PayrollController', 'index']) }}" class="btn btn-success btn-block">
                                <i class="fa fa-money"></i> Payroll
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-top:10px;">
                            <a href="{{ route('hrm.reports.index') }}" class="btn btn-info btn-block">
                                <i class="fa fa-bar-chart"></i> HRM Reports
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-top:10px;">
                            <a href="{{ route('hrm.settings.leave.edit') }}" class="btn btn-default btn-block">
                                <i class="fa fa-cog"></i> Default Leave Settings
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-top:10px;">
                            <a href="{{ route('hrm.settings.modules.edit') }}" class="btn btn-default btn-block">
                                <i class="fa fa-toggle-on"></i> HRM Modules
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
