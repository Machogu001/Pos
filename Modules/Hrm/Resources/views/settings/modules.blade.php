@extends('layouts.app')

@section('title', __('ui.hrm_module_settings'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.hrm_module_settings'),
    'subtitle' => __('ui.enable_or_disable_hrm_modules_and_jump_into_payroll_posting_configuration'),
    'actions' => '<a href="'.route('hrm.settings.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_settings') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.toggle_modules') }}</h3>
        </div>
        <form method="POST" action="{{ route('hrm.settings.modules.update') }}">
            @csrf
            <div class="box-body">
                <p class="help-block">{{ __('ui.select_which_modules_are_enabled_for_this_business_these_control_visibility_availability_across_menus_and_features') }}</p>

                <div class="row">
                    @foreach($moduleOptions as $key => $label)
                        <div class="col-md-4">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="modules[]" value="{{ $key }}" {{ in_array($key, $enabled ?? []) ? 'checked' : '' }}>
                                    {{ $label }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-primary">{{ __('ui.save_changes') }}</button>
            </div>
        </form>
    </div>

    <div class="box box-info" style="margin-top: 20px;">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.payroll_accounting_setup') }}</h3>
        </div>
        <div class="box-body">
            <p class="help-block">{{ __('ui.configure_the_accounts_used_when_payroll_is_processed_and_posted_to_the_chart_of_accounts') }}</p>
            <a href="{{ route('hrm.settings.payroll.edit') }}" class="btn btn-info">{{ __('ui.open_payroll_posting_settings') }}</a>
        </div>
    </div>
</section>
@endsection
