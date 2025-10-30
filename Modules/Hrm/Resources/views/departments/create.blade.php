@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Create Department</h2>
    <form method="POST" action="{{ route('hrm.departments.store') }}">
        @csrf

        <div class="form-group mb-3">
            <label for="department">Department</label>
            <input type="text" name="department" id="department" class="form-control" value="{{ old('department') }}" placeholder="e.g. Sales, Support" required />
        </div>

        <div class="form-group mb-3">
            <label for="company_id">Company</label>
            <select name="company_id" id="company_id" class="form-control" required>
                @foreach($companies as $c)
                    @php
                        $isBusiness = isset($c->business_id) && $c->business_id == session('business.id');
                        $label = $isBusiness ? 'Business - ' . $c->name : $c->name;
                    @endphp
                    <option value="{{ $c->id }}" @if(old('company_id', isset($default_company_id) ? $default_company_id : null) == $c->id) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group mb-3">
            <label for="department_head">Department Head (optional)</label>
            <select name="department_head" id="department_head" class="form-control">
                <option value="">-- None --</option>
                @foreach($employees as $e)
                    <option value="{{ $e->id }}" @if(old('department_head') == $e->id) selected @endif>{{ $e->username ?? $e->name }}</option>
                @endforeach
            </select>
            @error('department_head')
                <div class="text-danger small">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group mb-3">
            <label for="description">Description (optional)</label>
            <textarea name="description" id="description" class="form-control">{{ old('description') }}</textarea>
        </div>

        @error('department')
            <div class="text-danger small">{{ $message }}</div>
        @enderror

        <button class="btn btn-primary">Save</button>
        <a href="{{ route('hrm.departments.index') }}" class="btn btn-secondary ms-2">Cancel</a>
    </form>
</div>
@endsection
