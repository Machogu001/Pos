@extends('layouts.app')

@section('title', __('ui.create_department'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.create_department'),
    'subtitle' => __('ui.define_a_department_and_assign_it_to_the_right_company_and_department_head'),
    'actions' => '<a href="'.route('hrm_admin.departments.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_departments') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.department_details') }}</h3>
        </div>
        <form method="POST" action="{{ route('hrm_admin.departments.store') }}">
            @csrf
            @include('hrm::partials.hrm_form_toolbar')

            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="department">{{ __('ui.department') }}</label>
                            <input type="text" name="department" id="department" class="form-control" value="{{ old('department') }}" placeholder="{{ __('ui.e_g_sales_support') }}" required />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="company_id">{{ __('ui.company') }}</label>
                            <select name="company_id" id="company_id" class="form-control" required>
                                @foreach($companies as $c)
                                    @php
                                        $isBusiness = isset($c->business_id) && $c->business_id == session('business.id');
                                        $label = $isBusiness ? __('ui.business_2') . $c->name : $c->name;
                                    @endphp
                                    <option value="{{ $c->id }}" @if(old('company_id', isset($default_company_id) ? $default_company_id : null) == $c->id) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                @if($supportsDepartmentHead)
                    <div class="form-group">
                        <label for="department_head">{{ __('ui.department_head') }} <small class="text-muted">{{ __('ui.optional') }}</small></label>
                        @if(isset($employees) && count($employees) > 0)
                            <div class="input-group">
                                <select name="department_head" id="department_head" class="form-control">
                                    <option value="">{{ __('ui.none') }}</option>
                                    @foreach($employees as $e)
                                        @php $empLabel = $e->username ?? trim((($e->firstname ?? '') . ' ' . ($e->lastname ?? ''))); @endphp
                                        <option value="{{ $e->id }}" @if(old('department_head') == $e->id) selected @endif>{{ $empLabel ?: __('ui.employee_2') . $e->id }}</option>
                                    @endforeach
                                </select>
                                <span class="input-group-btn">
                                    <a href="{{ route('hrm.employees.create') }}" class="btn btn-default" target="_blank" rel="noopener">{{ __('ui.add_employee') }}</a>
                                </span>
                            </div>
                        @else
                            <div class="alert alert-info">{{ __('ui.no_employees_available_to_select_as_head') }} <a href="{{ route('hrm.employees.create') }}">{{ __('ui.create_one_first') }}</a>.</div>
                        @endif
                        @error('department_head')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                <div class="form-group">
                    <label for="description">{{ __('ui.description') }} <small class="text-muted">{{ __('ui.optional') }}</small></label>
                    <textarea name="description" id="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                </div>

                @error('department')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            <div class="box-footer text-right">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> {{ __('ui.save_department') }}</button>
            </div>
        </form>
    </div>
</section>
@endsection
