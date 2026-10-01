@extends('layouts.app')

@section('title', __('lang_v1.create_company'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('lang_v1.create_company'),
    'subtitle' => __('lang_v1.business_record_hrm_reporting'),
    'actions' => '<a href="'.route('hrm_admin.companies.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '.e(__('lang_v1.back_to_companies')).'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('lang_v1.company_details')</h3>
        </div>
        <form action="{{ route('hrm_admin.companies.store') }}" method="POST">
            {{ csrf_field() }}
            @include('hrm::partials.hrm_form_toolbar')
            <div class="box-body">
                @if(!empty($currentBusiness))
                    <div class="alert alert-info" style="margin-bottom: 20px;">
                        <label style="font-weight: 600; margin-bottom: 8px; display: block;">
                            <input type="checkbox" name="use_business_details" id="use_business_details" value="1" {{ old('use_business_details') ? 'checked' : '' }}>
                            @lang('lang_v1.use_current_business_details')
                        </label>
                        <input type="hidden" name="source_business_id" value="{{ $currentBusiness->id }}">
                        <div>
                            <strong>@lang('business.business'):</strong> {{ $currentBusiness->name }}
                        </div>
                        <small class="text-muted">@lang('lang_v1.can_edit_before_saving')</small>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">@lang('messages.name')</label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="{{ __('lang_v1.company_name') }}" value="{{ old('name', $currentBusiness->name ?? '') }}" required />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="country">@lang('business.country')</label>
                            <input type="text" name="country" id="country" class="form-control" placeholder="{{ __('lang_v1.country_name') }}" value="{{ old('country') }}" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">@lang('business.email')</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="{{ __('lang_v1.contact_company_email') }}" value="{{ old('email') }}" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">@lang('contact.phone')</label>
                            <input type="text" name="phone" id="phone" class="form-control" placeholder="{{ __('lang_v1.company_phone_placeholder') }}" value="{{ old('phone') }}" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer text-right">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> @lang('lang_v1.save_company')</button>
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
