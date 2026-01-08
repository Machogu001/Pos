@extends('layouts.app')

@section('content')
<div class="container">
    <h2>HRM Settings</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('hrm.settings.leave.update') }}">
        @csrf
        <div class="form-group">
            <label>Default annual leave (days)</label>
            <input name="default_annual_leave" type="number" min="0" step="1" class="form-control" value="{{ old('default_annual_leave', $default ?? config('hrm.default_annual_leave', 21)) }}" />
            <small class="form-text text-muted">This value will be used when an employee has no explicit remaining_leave set.</small>
        </div>
        <button class="btn btn-primary">Save</button>
    </form>
</div>
@endsection
