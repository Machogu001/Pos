@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Department</h2>
    <form action="/hrm/departments/{{ $department->id }}" method="POST">
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
            <select name="department_head" id="department_head" class="form-control">
                <option value="">-- None --</option>
                @foreach($employees as $e)
                    <option value="{{ $e->id }}" @if($department->department_head == $e->id) selected @endif>{{ $e->username }}</option>
                @endforeach
            </select>
        </div>

        <button class="btn btn-success">Update</button>
    </form>
</div>
@endsection
