@extends('layouts.app')

@section('title', __('ui.hrm_leave_settings'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.hrm_leave_settings'),
    'subtitle' => __('ui.set_the_default_annual_leave_entitlement_for_new_employees'),
    'actions' => '<a href="'.route('hrm.settings.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_settings') .'</a>'
])

<section class="content">
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.annual_leave_default') }}</h3>
        </div>
        <form method="POST" action="{{ route('hrm.settings.leave.update') }}">
            @csrf
            <div class="box-body">
                <div class="form-group">
                    <label for="default_annual_leave">{{ __('ui.default_annual_leave_days') }}</label>
                    <input id="default_annual_leave" name="default_annual_leave" type="number" min="0" step="1" class="form-control" value="{{ old('default_annual_leave', $default ?? config('hrm.default_annual_leave', 21)) }}" />
                    <small class="form-text text-muted">{{ __('ui.this_value_will_be_used_when_an_employee_has_no_explicit_remaining_leave_set') }}</small>
                </div>
            </div>
            <div class="box-footer text-right">
                <button class="btn btn-primary">{{ __('ui.save_changes') }}</button>
            </div>
        </form>
    </div>
</section>
@endsection
