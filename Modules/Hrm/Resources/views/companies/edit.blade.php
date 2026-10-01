@extends('layouts.app')

@section('title', __('ui.edit_company'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.edit_company'),
    'subtitle' => __('ui.update_the_company_profile_and_payroll_defaults_used_across_hrm'),
    'actions' => '<a href="'.route('hrm_admin.companies.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_companies') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.company_details') }}</h3>
        </div>
        <form action="{{ route('hrm_admin.companies.update', $company->id) }}" method="POST">
            {{ csrf_field() }}
            {{ method_field('PUT') }}
            @include('hrm::partials.hrm_form_toolbar')

            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">{{ __('ui.name') }}</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $company->name) }}" required />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="country">{{ __('ui.country') }}</label>
                            <input type="text" name="country" id="country" class="form-control" value="{{ old('country', $company->country) }}" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">{{ __('ui.email') }}</label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $company->email) }}" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">{{ __('ui.phone') }}</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $company->phone) }}" />
                        </div>
                    </div>
                </div>

                <div class="box box-default" style="box-shadow:none; margin-bottom:0;">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ __('ui.payroll_settings') }}</h3>
                    </div>
                    <div class="box-body">
                        <p class="text-muted">{{ __('ui.leave_these_blank_to_use_the_global_payroll_defaults') }}</p>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nssf_percent">{{ __('ui.nssf') }}</label>
                                    <input type="number" step="0.00001" min="0" name="nssf_percent" id="nssf_percent" class="form-control" value="{{ old('nssf_percent', $company->nssf_percent ?? '') }}" />
                                    <small class="form-text text-muted">{{ __('ui.enter_fraction_as_percent_e_g_0_0048_for_0_48') }}</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="shif_percent">{{ __('ui.shif') }}</label>
                                    <input type="number" step="0.00001" min="0" name="shif_percent" id="shif_percent" class="form-control" value="{{ old('shif_percent', $company->shif_percent ?? '') }}" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="housing_percent">{{ __('ui.housing_levy') }}</label>
                                    <input type="number" step="0.00001" min="0" name="housing_percent" id="housing_percent" class="form-control" value="{{ old('housing_percent', $company->housing_percent ?? '') }}" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tax_percent">{{ __('ui.tax') }}</label>
                                    <input type="number" step="0.00001" min="0" name="tax_percent" id="tax_percent" class="form-control" value="{{ old('tax_percent', $company->tax_percent ?? '') }}" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="personal_relief">{{ __('ui.personal_relief_amount') }}</label>
                                    <input type="number" step="0.01" min="0" name="personal_relief" id="personal_relief" class="form-control" value="{{ old('personal_relief', $company->personal_relief ?? '') }}" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box-footer text-right">
                <a href="{{ route('hrm_admin.companies.index') }}" class="btn btn-default">{{ __('ui.cancel') }}</a>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> {{ __('ui.update_company') }}</button>
            </div>
        </form>
    </div>
</section>
@endsection
