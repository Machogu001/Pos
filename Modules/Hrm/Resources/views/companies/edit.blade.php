@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Company</h2>
    <form action="{{ route('hrm.companies.update', $company->id) }}" method="POST">
        {{ csrf_field() }}
        {{ method_field('PUT') }}

        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ $company->name }}" required />
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" value="{{ $company->email }}" />
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" name="phone" id="phone" class="form-control" value="{{ $company->phone }}" />
        </div>

        <div class="form-group">
            <label for="country">Country</label>
            <input type="text" name="country" id="country" class="form-control" value="{{ $company->country }}" />
        </div>

        <h4>Payroll settings (optional — leave empty to use global defaults)</h4>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="nssf_percent">NSSF (%)</label>
                <input type="number" step="0.00001" min="0" name="nssf_percent" id="nssf_percent" class="form-control" value="{{ $company->nssf_percent ?? '' }}" />
                <small class="form-text text-muted">Enter fraction as percent (e.g. 0.0048 for 0.48%)</small>
            </div>
            <div class="form-group col-md-4">
                <label for="shif_percent">SHIF (%)</label>
                <input type="number" step="0.00001" min="0" name="shif_percent" id="shif_percent" class="form-control" value="{{ $company->shif_percent ?? '' }}" />
            </div>
            <div class="form-group col-md-4">
                <label for="housing_percent">Housing Levy (%)</label>
                <input type="number" step="0.00001" min="0" name="housing_percent" id="housing_percent" class="form-control" value="{{ $company->housing_percent ?? '' }}" />
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="tax_percent">Tax (%)</label>
                <input type="number" step="0.00001" min="0" name="tax_percent" id="tax_percent" class="form-control" value="{{ $company->tax_percent ?? '' }}" />
            </div>
            <div class="form-group col-md-4">
                <label for="personal_relief">Personal Relief (amount)</label>
                <input type="number" step="0.01" min="0" name="personal_relief" id="personal_relief" class="form-control" value="{{ $company->personal_relief ?? '' }}" />
            </div>
        </div>

    <button class="btn btn-success">Update</button>
    </form>
</div>
@endsection
