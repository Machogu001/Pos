@extends('layouts.app')

@section('title', __('ui.hrm_settings'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.hrm_settings'),
    'subtitle' => __('ui.configure_leave_defaults_payroll_posting_and_module_availability_from_one_place')
])

<section class="content">
    <div class="row">
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('ui.leave_settings') }}</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">{{ __('ui.default_annual_leave_is_currently_set_to') }} <strong>{{ number_format($defaultLeave) }}</strong> {{ __('ui.days_3') }}</p>
                    <a href="{{ route('hrm.settings.leave.edit') }}" class="btn btn-primary btn-block">{{ __('ui.open_leave_settings') }}</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('ui.payroll_posting') }}</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">{{ !empty($settings->payroll_auto_post) ? __('ui.payroll_is_set_to_post_automatically') : __('ui.payroll_auto_posting_is_disabled') }}</p>
                    <a href="{{ route('hrm.settings.payroll.edit') }}" class="btn btn-success btn-block">{{ __('ui.open_payroll_accounting_setup') }}</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('ui.module_controls') }}</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">{{ __('ui.enable_or_disable_hrm_related_business_modules_from_the_module_settings_screen') }}</p>
                    <a href="{{ route('hrm.settings.modules.edit') }}" class="btn btn-info btn-block">{{ __('ui.open_module_settings') }}</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('ui.theme_customization') }}</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">{{ __('ui.active_theme') }} <strong>{{ ucfirst($currentTheme ?? 'classic') }}</strong>. {{ __('ui.choose_how_hrm_pages_look') }}</p>
                    <div class="hrm-theme-quick-grid" aria-label="{{ __('ui.quick_theme_switch') }}">
                        <form method="POST" action="{{ route('hrm.settings.theme.update') }}">
                            @csrf
                            <input type="hidden" name="hrm_theme" value="classic">
                            <button type="submit" class="hrm-theme-quick-btn theme-classic {{ ($currentTheme ?? 'classic') === 'classic' ? 'active' : '' }}">
                                <span class="title">{{ __('ui.classic') }}</span>
                                <span class="hint">{{ __('ui.warm_premium') }}</span>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('hrm.settings.theme.update') }}">
                            @csrf
                            <input type="hidden" name="hrm_theme" value="corporate">
                            <button type="submit" class="hrm-theme-quick-btn theme-corporate {{ ($currentTheme ?? 'classic') === 'corporate' ? 'active' : '' }}">
                                <span class="title">{{ __('ui.corporate') }}</span>
                                <span class="hint">{{ __('ui.cool_and_clean') }}</span>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('hrm.settings.theme.update') }}">
                            @csrf
                            <input type="hidden" name="hrm_theme" value="minimal">
                            <button type="submit" class="hrm-theme-quick-btn theme-minimal {{ ($currentTheme ?? 'classic') === 'minimal' ? 'active' : '' }}">
                                <span class="title">{{ __('ui.minimal') }}</span>
                                <span class="hint">{{ __('ui.soft_neutral') }}</span>
                            </button>
                        </form>
                    </div>
                    <hr style="margin:10px 0; border-color:#e2d3b5;">
                    <a href="{{ route('hrm.settings.theme.edit') }}" class="btn btn-warning btn-block">{{ __('ui.open_theme_settings') }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection