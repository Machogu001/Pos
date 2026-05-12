@extends('layouts.app')

@section('title', __('ui.create_designation'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.create_designation'),
    'subtitle' => __('ui.create_a_job_title_and_link_it_to_the_right_company_and_department'),
    'actions' => '<a href="'.route('hrm.designations.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_designations') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.designation_details') }}</h3>
        </div>
        <form action="{{ route('hrm.designations.store') }}" method="POST">
            {{ csrf_field() }}
            @include('hrm::partials.hrm_form_toolbar')

            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="designation">{{ __('ui.designation') }}</label>
                            <input type="text" name="designation" id="designation" class="form-control" placeholder="{{ __('ui.e_g_manager_cashier') }}" required />
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
                                    <option value="{{ $c->id }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="department">{{ __('ui.department') }}</label>
                    <select name="department" id="department" class="form-control" required>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->department }}</option>
                        @endforeach
                    </select>
                    @include('hrm::partials.field_error', ['field' => 'department'])
                </div>
            </div>

            <div class="box-footer text-right">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> {{ __('ui.save_designation') }}</button>
            </div>
        </form>
    </div>
</section>
@endsection
