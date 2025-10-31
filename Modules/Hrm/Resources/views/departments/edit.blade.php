@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Department</h2>
    <form action="{{ route('hrm.departments.update', $department->id) }}" method="POST">
        {{ csrf_field() }}
        {{ method_field('PUT') }}

        <div class="form-group">
            <label for="department">Department</label>
            <input type="text" name="department" id="department" class="form-control" value="{{ $department->department }}" required />
        </div>

        <div class="form-group">
            <label for="company_id">Company</label>
            <select name="company_id" id="company_id" class="form-control" required>
                @foreach($companies as $c)
                    @php
                        $isBusiness = isset($c->business_id) && $c->business_id == session('business.id');
                        $label = $isBusiness ? 'Business - ' . $c->name : $c->name;
                    @endphp
                    <option value="{{ $c->id }}" @if($department->company_id == $c->id) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="department_head">Department Head (optional)</label>
            @if(isset($employees) && count($employees) > 0)
                <div class="d-flex gap-2">
                    <select name="department_head" id="department_head" class="form-control">
                        <option value="">-- None --</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}" @if($department->department_head == $e->id) selected @endif>{{ $e->username ?? $e->name }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('hrm.employees.create') }}" class="btn btn-outline-secondary" target="_blank" rel="noopener">Add Employee</a>
                </div>
            @else
                <div class="d-flex align-items-center gap-2">
                    <div class="text-muted">No employees available to select as head.</div>
                    <a href="{{ route('hrm.employees.create') }}" class="btn btn-primary" target="_blank" rel="noopener">Add Department Head</a>
                </div>
            @endif
        </div>

        <button class="btn btn-success">Update</button>
    </form>
</div>
@endsection
