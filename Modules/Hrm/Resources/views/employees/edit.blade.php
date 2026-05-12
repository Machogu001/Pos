@extends('layouts.app')

@section('title', __('ui.edit_employee'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.edit_employee'),
    'subtitle' => __('ui.update_the_employee_profile_payroll_and_leave_details'),
    'actions' => '<a href="'.route('hrm.employees.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_employees') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.employee_details') }}</h3>
        </div>
        <form method="POST" action="{{ route('hrm.employees.update', $employee->id) }}">
            @csrf
            @method('PUT')
            @include('hrm::partials.hrm_form_toolbar')
            @if ($errors->any())
                <div class="alert alert-danger mx-3">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="company_id">{{ __('ui.company') }}</label>
                            <select name="company_id" id="company_id" class="form-control select2">
                                @foreach($companies as $c)
                                    <option value="{{ $c->id }}" @if(old('company_id', $employee->company_id) == $c->id) selected @endif>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="country">{{ __('ui.country') }}</label>
                            <input name="country" id="country" class="form-control" value="{{ old('country', $employee->country) }}" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="gender">{{ __('ui.gender') }}</label>
                            <select name="gender" id="gender" class="form-control">
                                <option value="male" @if(strtolower((string) old('gender', $employee->gender)) == 'male') selected @endif>{{ __('ui.male') }}</option>
                                <option value="female" @if(strtolower((string) old('gender', $employee->gender)) == 'female') selected @endif>{{ __('ui.female') }}</option>
                                <option value="other" @if(strtolower((string) old('gender', $employee->gender)) == 'other') selected @endif>{{ __('ui.other') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="firstname">{{ __('ui.first_name') }}</label>
                            <input name="firstname" id="firstname" class="form-control" required value="{{ old('firstname', $employee->firstname) }}" />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="lastname">{{ __('ui.last_name') }}</label>
                            <input name="lastname" id="lastname" class="form-control" value="{{ old('lastname', $employee->lastname) }}" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="department_id">{{ __('ui.department') }}</label>
                            <select name="department_id" id="department_id" class="form-control">
                                <option value="">--</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}" @if(old('department_id', $employee->department_id) == $d->id) selected @endif>{{ $d->department ?? ($d->name ?? '') }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="designation_id">{{ __('ui.designation') }}</label>
                            <select name="designation_id" id="designation_id" class="form-control">
                                <option value="">--</option>
                                @foreach($designations as $des)
                                    <option value="{{ $des->id }}" @if(old('designation_id', $employee->designation_id) == $des->id) selected @endif>{{ $des->designation ?? ($des->name ?? '') }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="office_shift_id">{{ __('ui.office_shift_2') }}</label>
                            <select name="office_shift_id" id="office_shift_id" class="form-control">
                                <option value="">--</option>
                                @foreach($office_shifts as $os)
                                    <option value="{{ $os->id }}" @if(old('office_shift_id', $employee->office_shift_id) == $os->id) selected @endif>{{ $os->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">{{ __('ui.email') }}</label>
                            <input name="email" id="email" type="email" class="form-control" value="{{ old('email', $employee->email) }}" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">{{ __('ui.phone') }}</label>
                            <input name="phone" id="phone" class="form-control" value="{{ old('phone', $employee->phone) }}" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="joining_date">{{ __('ui.hire_date') }}</label>
                            <?php
                                $joiningDate = null;
                                if (!empty($employee->joining_date)) {
                                    try {
                                        $joiningDate = \Carbon\Carbon::parse($employee->joining_date);
                                    } catch (\Exception $e) {
                                        $joiningDate = null;
                                    }
                                } elseif (!empty($employee->hire_date)) {
                                    try {
                                        $joiningDate = \Carbon\Carbon::parse($employee->hire_date);
                                    } catch (\Exception $e) {
                                        $joiningDate = null;
                                    }
                                }
                                $joiningValue = old('joining_date', $joiningDate ? $joiningDate->format('Y-m-d') : '');
                            ?>
                            <input name="joining_date" id="joining_date" type="date" class="form-control" value="{{ $joiningValue }}" />
                        </div>
                    </div>
                </div>

                <div class="box box-default" style="box-shadow:none; margin-bottom:0;">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ __('ui.payroll_and_leave') }}</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="basic_salary">{{ __('ui.salary') }}</label>
                                    <input name="basic_salary" id="basic_salary" class="form-control" value="{{ old('basic_salary', $employee->basic_salary ?? $employee->salary ?? '') }}" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="total_leave">{{ __('ui.total_annual_leave_days') }}</label>
                                    <input id="total_leave" name="total_leave" type="number" min="0" step="1" class="form-control" value="{{ old('total_leave', $employee->total_leave ?? config('hrm.default_annual_leave', 21)) }}" />
                                    <small class="form-text text-muted">{{ __('ui.employee_s_total_annual_leave_entitlement') }}</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="remaining_leave">{{ __('ui.remaining_leave_days') }}</label>
                                    <input id="remaining_leave" name="remaining_leave" type="number" min="0" step="1" class="form-control" value="{{ old('remaining_leave', $employee->remaining_leave ?? $employee->total_leave ?? config('hrm.default_annual_leave', 21)) }}" />
                                    <small id="remaining_help" class="form-text text-muted">{{ __('ui.remaining_leave_days_must_be_less_than_or_equal_to_total_leave') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label for="notes">{{ __('ui.notes') }}</label>
                            <textarea name="notes" id="notes" class="form-control">{{ old('notes', $employee->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box-footer text-right">
                <a href="{{ route('hrm.employees.index') }}" class="btn btn-default">{{ __('ui.cancel') }}</a>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> {{ __('ui.update_employee') }}</button>
            </div>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    function showRemainingMessage(msg, isError){
        var help = document.getElementById('remaining_help');
        if (!help) return;
        help.textContent = msg;
        help.classList.toggle('text-danger', !!isError);
        help.classList.toggle('text-muted', !isError);
        setTimeout(function(){
            help.textContent = "{{ __('ui.remaining_leave_days_must_be_less_than_or_equal_to_total_leave') }}";
            help.classList.remove('text-danger');
            help.classList.add('text-muted');
        }, 3000);
    }
    function enforceLeaveBounds(){
        var totalEl = document.getElementById('total_leave');
        var remEl = document.getElementById('remaining_leave');
        if (!totalEl || !remEl) return;
        var total = parseInt(totalEl.value, 10);
        var rem = parseInt(remEl.value, 10);
        if (isNaN(total)) total = 0;
        if (isNaN(rem)) rem = 0;
        if (rem < 0) { remEl.value = 0; showRemainingMessage("{{ __('ui.remaining_cannot_be_negative') }}", true); }
        if (rem > total) { remEl.value = total; showRemainingMessage("{{ __('ui.remaining_cannot_exceed_total_adjusted_to_total') }}", true); }
    }
    var totalInput = document.getElementById('total_leave');
    var remainingInput = document.getElementById('remaining_leave');
    if (totalInput) totalInput.addEventListener('input', enforceLeaveBounds);
    if (remainingInput) remainingInput.addEventListener('input', enforceLeaveBounds);
    var formEl = document.querySelector('form');
    if (formEl) formEl.addEventListener('submit', enforceLeaveBounds);
});
</script>
@endpush
