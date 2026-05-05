@extends('layouts.app')

@section('title', 'Create Department')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Create Department',
    'subtitle' => 'Define a department and assign it to the right company and department head.',
    'actions' => '<a href="'.route('hrm.departments.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Departments</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Department Details</h3>
        </div>
        <form method="POST" action="{{ route('hrm.departments.store') }}">
            @csrf
            @include('hrm::partials.hrm_form_toolbar')

            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="department">Department</label>
                            <input type="text" name="department" id="department" class="form-control" value="{{ old('department') }}" placeholder="e.g. Sales, Support" required />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
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
                    </div>
                </div>

                <div class="form-group">
                    <label for="department_head">Department Head <small class="text-muted">optional</small></label>
                    @if(isset($employees) && count($employees) > 0)
                        <div class="input-group">
                            <select name="department_head" id="department_head" class="form-control">
                                <option value="">-- None --</option>
                                @foreach($employees as $e)
                                    @php $empLabel = $e->username ?? trim((($e->firstname ?? '') . ' ' . ($e->lastname ?? ''))); @endphp
                                    <option value="{{ $e->id }}" @if(old('department_head') == $e->id) selected @endif>{{ $empLabel ?: 'Employee #'.$e->id }}</option>
                                @endforeach
                            </select>
                            <span class="input-group-btn">
                                <a href="{{ route('hrm.employees.create') }}" class="btn btn-default" target="_blank" rel="noopener">Add Employee</a>
                            </span>
                        </div>
                    @else
                        <div class="alert alert-info">No employees available to select as head. <a href="{{ route('hrm.employees.create') }}">Create one first</a>.</div>
                    @endif
                    @error('department_head')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description">Description <small class="text-muted">optional</small></label>
                    <textarea name="description" id="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                </div>

                @error('department')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            <div class="box-footer text-right">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Department</button>
            </div>
        </form>
    </div>
</section>
@endsection
