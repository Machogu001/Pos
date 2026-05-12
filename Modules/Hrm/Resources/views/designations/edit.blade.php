@extends('layouts.app')

@section('title', __('ui.edit_designation'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.edit_designation'),
    'subtitle' => __('ui.adjust_the_role_title_and_its_department_mapping'),
    'actions' => '<a href="'.route('hrm.designations.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_designations') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.designation_details') }}</h3>
        </div>
        <form action="{{ route('hrm.designations.update', $designation->id) }}" method="POST">
            {{ csrf_field() }}
            {{ method_field('PUT') }}
            @include('hrm::partials.hrm_form_toolbar')

            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="designation">{{ __('ui.designation') }}</label>
                            <input type="text" name="designation" id="designation" class="form-control" value="{{ old('designation', $designation->designation) }}" required />
                            @include('hrm::partials.field_error', ['field' => 'designation'])
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
                                    <option value="{{ $c->id }}" @if(old('company_id', $designation->company_id) == $c->id) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="department">{{ __('ui.department') }}</label>
                            <select name="department" id="department" class="form-control" required>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}" @if(old('department', $designation->department_id) == $d->id) selected @endif>{{ $d->department }}</option>
                                @endforeach
                            </select>
                            @include('hrm::partials.field_error', ['field' => 'department'])
                        </div>
                    </div>
                </div>
            </div>

            <div class="box-footer text-right">
                <a href="{{ route('hrm.designations.index') }}" class="btn btn-default">{{ __('ui.cancel') }}</a>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> {{ __('ui.update_designation') }}</button>
            </div>
        </form>
    </div>
</section>
@endsection
