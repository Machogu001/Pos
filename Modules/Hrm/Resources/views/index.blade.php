@extends('layouts.app')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.human_resource_management'),
    'subtitle' => __('ui.operational_dashboard'),
    'actions' => '<a href="'.route('hrm.reports.index').'" class="btn btn-default"><i class="fa fa-bar-chart"></i> '. __('ui.open_reports') .'</a>'
])

<section class="content">
    @if(!empty($alerts))
        <div class="row">
            <div class="col-xs-12">
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h2 class="box-title h3">{{ __('ui.attention_needed') }}</h2>
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
                    <p>{{ __('ui.total_employees_2') }}</p>
                </div>
                <div class="icon">
                    <i class="fa fa-users"></i>
                </div>
                <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\EmployeesController', 'index']) }}" class="small-box-footer">
                    {{ __('ui.manage_employees') }} <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-green hrm-kpi hrm-kpi-active">
                <div class="inner">
                    <h3>{{ number_format($stats['active_employees'] ?? 0) }}</h3>
                    <p>{{ __('ui.active_employees') }}</p>
                </div>
                <div class="icon">
                    <i class="fa fa-user"></i>
                </div>
                <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\EmployeesController', 'index']) }}" class="small-box-footer">
                    {{ __('ui.view_active_staff') }} <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-yellow hrm-kpi hrm-kpi-leave">
                <div class="inner">
                    <h3>{{ number_format($stats['on_leave_today'] ?? 0) }}</h3>
                    <p>{{ __('ui.on_leave_today') }}</p>
                </div>
                <div class="icon">
                    <i class="fa fa-plane"></i>
                </div>
                <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\LeaveController', 'index']) }}" class="small-box-footer">
                    {{ __('ui.view_leave_calendar') }} <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-red hrm-kpi hrm-kpi-risk">
                <div class="inner">
                    <h3>{{ number_format($stats['pending_leaves'] ?? 0) }}</h3>
                    <p>{{ __('ui.pending_leave_requests') }}</p>
                </div>
                <div class="icon">
                    <i class="fa fa-clock-o"></i>
                </div>
                <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\LeaveController', 'index']) }}" class="small-box-footer">
                    {{ __('ui.review_requests') }} <i class="fa fa-arrow-circle-right"></i>
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
                    <p>{{ __('ui.this_month_payroll_net') }}</p>
                </div>
                <div class="icon">
                    <i class="fa fa-money"></i>
                </div>
                <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\PayrollController', 'index']) }}" class="small-box-footer">
                    {{ __('ui.open_payroll') }} <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-teal hrm-kpi hrm-kpi-attendance">
                <div class="inner">
                    <h3>{{ number_format($stats['today_clocked_in'] ?? 0) }}</h3>
                    <p>{{ __('ui.clocked_in_today') }}</p>
                </div>
                <div class="icon">
                    <i class="fa fa-sign-in"></i>
                </div>
                <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\AttendancesController', 'index']) }}" class="small-box-footer">
                    {{ __('ui.attendance_sheet') }} <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-orange hrm-kpi hrm-kpi-attention">
                <div class="inner">
                    <h3>{{ number_format($stats['absent_estimate_today'] ?? 0) }}</h3>
                    <p>{{ __('ui.estimated_not_clocked_in') }}</p>
                </div>
                <div class="icon">
                    <i class="fa fa-user-times"></i>
                </div>
                <a href="#clocked-in-list" class="small-box-footer">
                    {{ __('ui.view_clocked_in') }} <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-maroon hrm-kpi hrm-kpi-structure">
                <div class="inner">
                    <h3>{{ number_format($stats['total_departments'] ?? 0) }}</h3>
                    <p>{{ __('ui.departments_configured') }}</p>
                </div>
                <div class="icon">
                    <i class="fa fa-sitemap"></i>
                </div>
                <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\DepartmentsController', 'index']) }}" class="small-box-footer">
                    {{ __('ui.open_departments') }} <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <div class="hrm-kpi-legend" aria-label="{{ __('ui.kpi_legend') }}">
                <span class="hrm-kpi-legend-title">{{ __('ui.kpi_colors') }}</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-people"></span> {{ __('ui.people') }}</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-active"></span> {{ __('ui.active') }}</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-leave"></span> {{ __('ui.leave') }}</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-risk"></span> {{ __('ui.risk') }}</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-payroll"></span> {{ __('ui.payroll') }}</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-attendance"></span> {{ __('ui.attendance') }}</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-attention"></span> {{ __('ui.attention') }}</span>
                <span class="hrm-kpi-chip"><span class="hrm-kpi-dot hrm-kpi-structure"></span> {{ __('ui.structure') }}</span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 col-xs-12">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h2 class="box-title h3">{{ __('ui.recent_pending_leave_requests') }}</h2>
                    <div class="box-tools pull-right">
                        <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\LeaveController', 'index']) }}" class="btn btn-xs btn-default">{{ __('ui.view_all') }}</a>
                    </div>
                </div>
                <div class="box-body no-padding">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" style="margin-bottom:0;" aria-label="{{ __('ui.recent_pending_leave_requests_2') }}">
                            <thead>
                                <tr>
                                    <th>{{ __('ui.employee') }}</th>
                                    <th>{{ __('ui.type') }}</th>
                                    <th>{{ __('ui.period') }}</th>
                                    <th>{{ __('ui.days_2') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPendingLeaves as $leave)
                                    <tr>
                                        <td>{{ $leave->employee_name ?: 'Employee #'.$leave->employee_id }}</td>
                                        <td>{{ $leave->leave_type_name ?: __('ui.general') }}</td>
                                        <td>{{ $leave->start_date }} {{ __('ui.to') }} {{ $leave->end_date }}</td>
                                        <td>{{ $leave->days }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">{{ __('ui.no_pending_leave_requests') }}</td>
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
                    <h2 class="box-title h3">{{ __('ui.recent_payroll_runs') }}</h2>
                    <div class="box-tools pull-right">
                        <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\PayrollController', 'index']) }}" class="btn btn-xs btn-default">{{ __('ui.view_all') }}</a>
                    </div>
                </div>
                <div class="box-body no-padding">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" style="margin-bottom:0;" aria-label="{{ __('ui.recent_payroll_runs_2') }}">
                            <thead>
                                <tr>
                                    <th>{{ __('ui.run_id') }}</th>
                                    <th>{{ __('ui.period') }}</th>
                                    <th class="text-right">{{ __('ui.net') }}</th>
                                    <th>{{ __('ui.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPayrollRuns as $run)
                                    <tr>
                                        <td>#{{ $run->id }}</td>
                                        <td>
                                            @if(isset($run->period_start) && isset($run->period_end))
                                                {{ $run->period_start }} {{ __('ui.to') }} {{ $run->period_end }}
                                            @else
                                                {{ ($run->month ?? '-') }}/{{ ($run->year ?? '-') }}
                                            @endif
                                        </td>
                                        <td class="text-right">{{ number_format((float) ($run->net ?? 0), 2) }}</td>
                                        <td>
                                            @if(isset($run->posted_to_accounts))
                                                @if((int) $run->posted_to_accounts === 1)
                                                    <span class="label label-success">{{ __('ui.posted') }}</span>
                                                @else
                                                    <span class="label label-default">{{ __('ui.saved') }}</span>
                                                @endif
                                            @else
                                                <span class="label label-default">{{ __('ui.saved') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">{{ __('ui.no_payroll_runs_yet') }}</td>
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
                    <h2 class="box-title h3">{{ __('ui.clocked_in_today') }}</h2>
                    <div class="box-tools pull-right">
                        <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\AttendancesController', 'index']) }}" class="btn btn-xs btn-default">{{ __('ui.open_attendance') }}</a>
                    </div>
                </div>
                <div class="box-body no-padding">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" style="margin-bottom:0;" aria-label="{{ __('ui.clocked_in_employees_today') }}">
                            <thead>
                                <tr>
                                    <th>{{ __('ui.employee') }}</th>
                                    <th>{{ __('ui.username') }}</th>
                                    <th>{{ __('ui.clock_in') }}</th>
                                    <th>{{ __('ui.clock_out') }}</th>
                                    <th class="text-right">{{ __('ui.action') }}</th>
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
                                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\EmployeesController', 'edit'], ['employee' => $emp->id]) }}" class="btn btn-xs btn-default">{{ __('ui.open_profile') }}</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">{{ __('ui.no_employees_have_clocked_in_today') }}</td>
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
                    <h2 class="box-title h3">{{ __('ui.not_clocked_in_today_all') }}</h2>
                    <div class="box-tools pull-right">
                        <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\AttendancesController', 'index']) }}" class="btn btn-xs btn-default">{{ __('ui.open_attendance') }}</a>
                    </div>
                </div>
                <div class="box-body no-padding">
                    @if(!empty($notClockedInTruncated))
                        <div class="alert alert-info" style="margin:10px;">
                            {{ __('Showing first :limit records for performance. Refine attendance review for full detail.', ['limit' => $notClockedDisplayLimit ?? 500]) }}
                        </div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" style="margin-bottom:0;" aria-label="{{ __('ui.employees_not_clocked_in_today') }}">
                            <thead>
                                <tr>
                                    <th>{{ __('ui.employee') }}</th>
                                    <th>{{ __('ui.username') }}</th>
                                    <th class="text-right">{{ __('ui.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($notClockedInToday ?? []) as $emp)
                                    <tr>
                                        <td>{{ trim(($emp->firstname ?? '').' '.($emp->lastname ?? '')) ?: ('Employee #'.$emp->id) }}</td>
                                        <td>{{ $emp->username ?: '-' }}</td>
                                        <td class="text-right">
                                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\EmployeesController', 'edit'], ['employee' => $emp->id]) }}" class="btn btn-xs btn-default">{{ __('ui.open_profile') }}</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">{{ __('ui.everyone_is_either_clocked_in_or_on_approved_leave_today') }}</td>
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
                    <h3 class="box-title">{{ __('ui.hrm_quick_links') }}</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-3 col-sm-4 col-xs-6">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\CompanyController', 'index']) }}" class="btn btn-default btn-block">
                                <i class="fa fa-building"></i> {{ __('ui.companies') }}
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\DepartmentsController', 'index']) }}" class="btn btn-default btn-block">
                                <i class="fa fa-sitemap"></i> {{ __('ui.departments') }}
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\DesignationsController', 'index']) }}" class="btn btn-default btn-block">
                                <i class="fa fa-id-badge"></i> {{ __('ui.designations') }}
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\OfficeShiftController', 'index']) }}" class="btn btn-default btn-block">
                                <i class="fa fa-calendar"></i> {{ __('ui.office_shifts') }}
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-top:10px;">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\AttendancesController', 'index']) }}" class="btn btn-default btn-block">
                                <i class="fa fa-clock-o"></i> {{ __('ui.attendance') }}
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-top:10px;">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\HolidayController', 'index']) }}" class="btn btn-default btn-block">
                                <i class="fa fa-calendar-times-o"></i> {{ __('ui.holidays') }}
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-top:10px;">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\EmployeesController', 'index']) }}" class="btn btn-primary btn-block">
                                <i class="fa fa-users"></i> {{ __('ui.employees') }}
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-top:10px;">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\LeaveController', 'index']) }}" class="btn btn-primary btn-block">
                                <i class="fa fa-plane"></i> {{ __('ui.leaves') }}
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-top:10px;">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\LeaveTypeController', 'index']) }}" class="btn btn-default btn-block">
                                <i class="fa fa-tags"></i> {{ __('ui.leave_types') }}
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-top:10px;">
                            <a href="{{ action(['\\Modules\\Hrm\\Http\\Controllers\\PayrollController', 'index']) }}" class="btn btn-success btn-block">
                                <i class="fa fa-money"></i> {{ __('ui.payroll') }}
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-top:10px;">
                            <a href="{{ route('hrm.reports.index') }}" class="btn btn-info btn-block">
                                <i class="fa fa-bar-chart"></i> {{ __('ui.hrm_reports') }}
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-top:10px;">
                            <a href="{{ route('hrm.settings.leave.edit') }}" class="btn btn-default btn-block">
                                <i class="fa fa-cog"></i> {{ __('ui.default_leave_settings') }}
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-top:10px;">
                            <a href="{{ route('hrm.settings.modules.edit') }}" class="btn btn-default btn-block">
                                <i class="fa fa-toggle-on"></i> {{ __('ui.hrm_modules') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
