@extends('layouts.app')

@section('title', 'HRM Leave Settings')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'HRM Leave Settings',
    'subtitle' => 'Set the default annual leave entitlement for new employees.',
    'actions' => '<a href="'.route('hrm.settings.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Settings</a>'
])

<section class="content">
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Annual Leave Default</h3>
        </div>
        <form method="POST" action="{{ route('hrm.settings.leave.update') }}">
            @csrf
            <div class="box-body">
                <div class="form-group">
                    <label for="default_annual_leave">Default annual leave (days)</label>
                    <input id="default_annual_leave" name="default_annual_leave" type="number" min="0" step="1" class="form-control" value="{{ old('default_annual_leave', $default ?? config('hrm.default_annual_leave', 21)) }}" />
                    <small class="form-text text-muted">This value will be used when an employee has no explicit remaining_leave set.</small>
                </div>
            </div>
            <div class="box-footer text-right">
                <button class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</section>
@endsection
