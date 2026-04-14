@extends('layouts.app')

@section('title', 'Edit Company')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Edit Company',
    'subtitle' => 'Update the company profile and payroll defaults used across HRM.',
    'actions' => '<a href="'.route('hrm.companies.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Companies</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Company Details</h3>
        </div>
        <form action="{{ route('hrm.companies.update', $company->id) }}" method="POST">
            {{ csrf_field() }}
            {{ method_field('PUT') }}
            @include('hrm::partials.hrm_form_toolbar')

            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $company->name) }}" required />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="country">Country</label>
                            <input type="text" name="country" id="country" class="form-control" value="{{ old('country', $company->country) }}" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $company->email) }}" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $company->phone) }}" />
                        </div>
                    </div>
                </div>

                <div class="box box-default" style="box-shadow:none; margin-bottom:0;">
                    <div class="box-header with-border">
                        <h3 class="box-title">Payroll Settings</h3>
                    </div>
                    <div class="box-body">
                        <p class="text-muted">Leave these blank to use the global payroll defaults.</p>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nssf_percent">NSSF (%)</label>
                                    <input type="number" step="0.00001" min="0" name="nssf_percent" id="nssf_percent" class="form-control" value="{{ old('nssf_percent', $company->nssf_percent ?? '') }}" />
                                    <small class="form-text text-muted">Enter fraction as percent (e.g. 0.0048 for 0.48%).</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="shif_percent">SHIF (%)</label>
                                    <input type="number" step="0.00001" min="0" name="shif_percent" id="shif_percent" class="form-control" value="{{ old('shif_percent', $company->shif_percent ?? '') }}" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="housing_percent">Housing Levy (%)</label>
                                    <input type="number" step="0.00001" min="0" name="housing_percent" id="housing_percent" class="form-control" value="{{ old('housing_percent', $company->housing_percent ?? '') }}" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tax_percent">Tax (%)</label>
                                    <input type="number" step="0.00001" min="0" name="tax_percent" id="tax_percent" class="form-control" value="{{ old('tax_percent', $company->tax_percent ?? '') }}" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="personal_relief">Personal Relief (amount)</label>
                                    <input type="number" step="0.01" min="0" name="personal_relief" id="personal_relief" class="form-control" value="{{ old('personal_relief', $company->personal_relief ?? '') }}" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box-footer text-right">
                <a href="{{ route('hrm.companies.index') }}" class="btn btn-default">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update Company</button>
            </div>
        </form>
    </div>
</section>
@endsection
