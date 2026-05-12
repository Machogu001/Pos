@extends('layouts.app')

@section('title', __('ui.employee_profile'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.employee_profile'),
    'subtitle' => trim(($employee->firstname ?? '').' '.($employee->lastname ?? '')),
    'actions' => '<a href="'.route('hrm.employees.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_employees') .'</a>'
])

<section class="content">
    <div class="row">
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('ui.profile_summary') }}</h3>
                </div>
                <div class="box-body">
                    <p><strong>{{ __('ui.company_2') }}</strong> {{ optional($employee->company)->name }}</p>
                    <p><strong>{{ __('ui.department_2') }}</strong> {{ optional($employee->department)->department }}</p>
                    <p><strong>{{ __('ui.designation_2') }}</strong> {{ optional($employee->designation)->designation }}</p>
                    <p><strong>{{ __('ui.total_annual_leave') }}</strong> {{ $employee->total_leave ?? config('hrm.default_annual_leave', 21) }} {{ __('ui.days') }}</p>
                    <p><strong>{{ __('ui.remaining_leave') }}</strong> {{ $employee->remaining_leave ?? ($employee->total_leave ?? config('hrm.default_annual_leave', 21)) }} {{ __('ui.days') }}</p>
                    <p><strong>{{ __('ui.phone_2') }}</strong> {{ $employee->phone }}</p>
                    <p><strong>{{ __('ui.email_2') }}</strong> {{ $employee->email }}</p>
                    <p><strong>{{ __('ui.suspended') }}</strong> {{ $employee->suspended ? __('ui.yes') : __('ui.no') }}</p>

                    <form method="POST" action="{{ route('hrm.employees.suspend', $employee->id) }}">
                        @csrf
                        <input type="hidden" name="action" value="{{ $employee->suspended ? 'unsuspend' : 'suspend' }}" />
                        <button class="btn btn-{{ $employee->suspended ? 'success' : 'warning' }} btn-block">{{ $employee->suspended ? __('ui.unsuspend_payroll') : __('ui.suspend_payroll') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('ui.deductions') }}</h3>
                </div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hrm.employees.deductions.store', $employee->id) }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('ui.amount') }}</label>
                                    <input name="amount" class="form-control" required />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('ui.type') }}</label>
                                    <input name="type" class="form-control" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('ui.reason') }}</label>
                                    <input name="reason" class="form-control" />
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-primary">{{ __('ui.add_deduction') }}</button>
                    </form>
                </div>

                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover table-striped mb-0">
                        <thead><tr><th>{{ __('ui.amount') }}</th><th>{{ __('ui.type') }}</th><th>{{ __('ui.reason') }}</th><th>{{ __('ui.date') }}</th><th class="text-right">{{ __('ui.action') }}</th></tr></thead>
                        <tbody>
                            @forelse($deductions as $d)
                                <tr>
                                    <td>{{ $d->amount }}</td>
                                    <td>{{ $d->type }}</td>
                                    <td>{{ $d->reason }}</td>
                                    <td>{{ $d->created_at }}</td>
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('hrm.employees.deductions.destroy', [$employee->id, $d->id]) }}" style="display:inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger">{{ __('ui.delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">{{ __('ui.no_deductions_found') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
