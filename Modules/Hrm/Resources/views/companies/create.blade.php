@extends('layouts.app')

@section('title', 'Create Company')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Create Company',
    'subtitle' => 'Add a business record used throughout HRM, payroll, and reporting.',
    'actions' => '<a href="'.route('hrm.companies.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Companies</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Company Details</h3>
        </div>
        <form action="{{ route('hrm.companies.store') }}" method="POST">
            {{ csrf_field() }}
            @include('hrm::partials.hrm_form_toolbar')
            <div class="box-body">
                @if(!empty($currentBusiness))
                    <div class="alert alert-info" style="margin-bottom: 20px;">
                        <label style="font-weight: 600; margin-bottom: 8px; display: block;">
                            <input type="checkbox" name="use_business_details" id="use_business_details" value="1" {{ old('use_business_details') ? 'checked' : '' }}>
                            Use current business details
                        </label>
                        <input type="hidden" name="source_business_id" value="{{ $currentBusiness->id }}">
                        <div>
                            <strong>Business:</strong> {{ $currentBusiness->name }}
                        </div>
                        <small class="text-muted">You can still edit any field before saving.</small>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="Company name" value="{{ old('name', $currentBusiness->name ?? '') }}" required />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="country">Country</label>
                            <input type="text" name="country" id="country" class="form-control" placeholder="Country name" value="{{ old('country') }}" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="contact@company.com" value="{{ old('email') }}" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control" placeholder="07xxxxxxxx" value="{{ old('phone') }}" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer text-right">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Company</button>
            </div>
        </form>
    </div>
</section>

<script>
    (function () {
        var useBusiness = document.getElementById('use_business_details');
        var name = document.getElementById('name');
        if (!useBusiness || !name) {
            return;
        }

        var businessName = @json($currentBusiness->name ?? '');

        function syncNameFromBusiness() {
            if (useBusiness.checked && !name.value.trim() && businessName) {
                name.value = businessName;
            }
            name.required = !useBusiness.checked;
        }

        useBusiness.addEventListener('change', syncNameFromBusiness);
        syncNameFromBusiness();
    })();
</script>
@endsection
