@extends('layouts.app')

@section('title', __('ui.create_attendance'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.create_attendance'),
    'subtitle' => __('ui.record_a_daily_attendance_entry'),
    'actions' => '<a href="'.route('hrm.attendances.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_attendance') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <form method="POST" action="{{ route('hrm.attendances.store') }}">
            @csrf
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ __('ui.company') }}</label>
                            <select name="company_id" class="form-control" required>
                                <option value="">{{ __('ui.select_company') }}</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ __('ui.employee') }}</label>
                            <select name="employee_id" class="form-control" required>
                                <option value="">{{ __('ui.select_employee') }}</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->username }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('ui.date') }}</label>
                            <input type="date" name="date" class="form-control" value="{{ old('date') }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('ui.clock_in') }}</label>
                            <input type="time" name="clock_in" class="form-control" value="{{ old('clock_in') }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('ui.clock_out') }}</label>
                            <input type="time" name="clock_out" class="form-control" value="{{ old('clock_out') }}" required>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer text-right">
                <button class="btn btn-primary">{{ __('ui.save_attendance') }}</button>
            </div>
        </form>
    </div>
</section>
@endsection