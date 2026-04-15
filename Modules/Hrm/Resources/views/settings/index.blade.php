@extends('layouts.app')

@section('title', 'HRM Settings')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'HRM Settings',
    'subtitle' => 'Configure leave defaults, payroll posting, and module availability from one place.'
])

<section class="content">
    <div class="row">
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Leave Settings</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">Default annual leave is currently set to <strong>{{ number_format($defaultLeave) }}</strong> days.</p>
                    <a href="{{ route('hrm.settings.leave.edit') }}" class="btn btn-primary btn-block">Open Leave Settings</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">Payroll Posting</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">{{ !empty($settings->payroll_auto_post) ? 'Payroll is set to post automatically.' : 'Payroll auto-posting is disabled.' }}</p>
                    <a href="{{ route('hrm.settings.payroll.edit') }}" class="btn btn-success btn-block">Open Payroll Accounting Setup</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Module Controls</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">Enable or disable HRM-related business modules from the module settings screen.</p>
                    <a href="{{ route('hrm.settings.modules.edit') }}" class="btn btn-info btn-block">Open Module Settings</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">Theme Customization</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">Active theme: <strong>{{ ucfirst($currentTheme ?? 'classic') }}</strong>. Choose how HRM pages look.</p>
                    <div class="hrm-theme-quick-grid" aria-label="Quick theme switch">
                        <form method="POST" action="{{ route('hrm.settings.theme.update') }}">
                            @csrf
                            <input type="hidden" name="hrm_theme" value="classic">
                            <button type="submit" class="hrm-theme-quick-btn theme-classic {{ ($currentTheme ?? 'classic') === 'classic' ? 'active' : '' }}">
                                <span class="title">Classic</span>
                                <span class="hint">Warm premium</span>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('hrm.settings.theme.update') }}">
                            @csrf
                            <input type="hidden" name="hrm_theme" value="corporate">
                            <button type="submit" class="hrm-theme-quick-btn theme-corporate {{ ($currentTheme ?? 'classic') === 'corporate' ? 'active' : '' }}">
                                <span class="title">Corporate</span>
                                <span class="hint">Cool and clean</span>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('hrm.settings.theme.update') }}">
                            @csrf
                            <input type="hidden" name="hrm_theme" value="minimal">
                            <button type="submit" class="hrm-theme-quick-btn theme-minimal {{ ($currentTheme ?? 'classic') === 'minimal' ? 'active' : '' }}">
                                <span class="title">Minimal</span>
                                <span class="hint">Soft neutral</span>
                            </button>
                        </form>
                    </div>
                    <hr style="margin:10px 0; border-color:#e2d3b5;">
                    <a href="{{ route('hrm.settings.theme.edit') }}" class="btn btn-warning btn-block">Open Theme Settings</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection