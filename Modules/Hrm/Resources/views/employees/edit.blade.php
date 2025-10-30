@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Employee</h2>
    <form method="POST" action="{{ route('hrm.employees.update', $employee->id) }}">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label>First name</label>
            <input name="first_name" class="form-control" required value="{{ $employee->first_name }}" />
        </div>
        <div class="form-group">
            <label>Last name</label>
            <input name="last_name" class="form-control" value="{{ $employee->last_name }}" />
        </div>
        <div class="form-group">
            <label>Department</label>
            <select name="department_id" class="form-control">
                <option value="">--</option>
                @foreach($departments as $d)
                    <option value="{{ $d->id }}" @if($employee->department_id == $d->id) selected @endif>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input name="email" type="email" class="form-control" value="{{ $employee->email }}" />
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input name="phone" class="form-control" value="{{ $employee->phone }}" />
        </div>
        <div class="form-group">
            <label>Hire date</label>
            <input name="hire_date" type="date" class="form-control" value="{{ $employee->hire_date ? $employee->hire_date->format('Y-m-d') : '' }}" />
        </div>
        <div class="form-group">
            <label>Salary</label>
            <input name="salary" class="form-control" value="{{ $employee->salary }}" />
        </div>
        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" class="form-control">{{ $employee->notes }}</textarea>
        </div>
        <button class="btn btn-primary">Save</button>
    </form>
</div>
@endsection
