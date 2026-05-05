@extends('layouts.app')

@section('title', 'Edit Department')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Edit Department',
    'subtitle' => 'Update the department structure and its assigned head.',
    'actions' => '<a href="'.route('hrm.departments.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Departments</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Department Details</h3>
        </div>
        <form action="{{ route('hrm.departments.update', $department->id) }}" method="POST">
            {{ csrf_field() }}
            {{ method_field('PUT') }}
            @include('hrm::partials.hrm_form_toolbar')

            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="department">Department</label>
                            <input type="text" name="department" id="department" class="form-control" value="{{ old('department', $department->department) }}" required />
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
                                    <option value="{{ $c->id }}" @if(old('company_id', $department->company_id) == $c->id) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="box box-default" style="box-shadow:none; margin-bottom:0;">
                    <div class="box-header with-border">
                        <h3 class="box-title">Department Head</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group mb-0">
                            <label for="department_head">Department Head (optional)</label>
                            @if(isset($employees) && count($employees) > 0)
                                <div class="d-flex gap-2">
                                    <select name="department_head" id="department_head" class="form-control">
                                        <option value="">-- None --</option>
                                        @foreach($employees as $e)
                                            @php $empLabel = $e->username ?? trim((($e->firstname ?? '') . ' ' . ($e->lastname ?? ''))); @endphp
                                            <option value="{{ $e->id }}" @if(old('department_head', $department->department_head) == $e->id) selected @endif>{{ $empLabel ?: 'Employee #'.$e->id }}</option>
                                        @endforeach
                                    </select>
                                    <a href="{{ route('hrm.employees.create') }}" class="btn btn-default" target="_blank" rel="noopener">Add Employee</a>
                                </div>
                            @else
                                <div class="d-flex align-items-center gap-2">
                                    <div class="text-muted">No employees available to select as head.</div>
                                    <a href="{{ route('hrm.employees.create') }}" class="btn btn-primary" target="_blank" rel="noopener">Add Department Head</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="box-footer text-right">
                <a href="{{ route('hrm.departments.index') }}" class="btn btn-default">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update Department</button>
            </div>
        </form>
    </div>
</section>
@endsection
