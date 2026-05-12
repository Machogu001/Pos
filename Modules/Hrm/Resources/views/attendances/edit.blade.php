@extends('layouts.app')

@section('title', __('ui.edit_attendance'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.edit_attendance'),
    'subtitle' => __('ui.update_an_attendance_entry'),
    'actions' => '<a href="'.route('hrm.attendances.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_attendance') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <form method="POST" action="{{ route('hrm.attendances.update', $attendance->id) }}">
            @csrf
            @method('PUT')
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ __('ui.company') }}</label>
                            <select name="company_id" class="form-control" required>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ old('company_id', $attendance->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ __('ui.employee') }}</label>
                            <select name="employee_id" class="form-control" required>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ old('employee_id', $attendance->employee_id) == $employee->id ? 'selected' : '' }}>{{ $employee->username }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('ui.date') }}</label>
                            <input type="date" name="date" class="form-control" value="{{ old('date', $attendance->date) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('ui.clock_in') }}</label>
                            <input type="time" name="clock_in" class="form-control" value="{{ old('clock_in', $attendance->clock_in) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('ui.clock_out') }}</label>
                            <input type="time" name="clock_out" class="form-control" value="{{ old('clock_out', $attendance->clock_out) }}" required>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer text-right">
                <button class="btn btn-primary">{{ __('ui.update_attendance') }}</button>
            </div>
        </form>
    </div>
</section>
@endsection