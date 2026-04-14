@extends('layouts.app')

@section('title', 'HRM Settings')

@section('content')
<section class="content-header">
    <h1>HRM Settings</h1>
    <p class="text-muted">Configure leave defaults, payroll posting, and module availability from one place.</p>
</section>

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
    </div>
</section>
@endsection