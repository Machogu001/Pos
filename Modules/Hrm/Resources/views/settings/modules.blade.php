@extends('layouts.app')

@section('title', __('HRM Module Settings'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'HRM Module Settings',
    'subtitle' => 'Enable or disable HRM modules and jump into payroll posting configuration.',
    'actions' => '<a href="'.route('hrm.settings.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Settings</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Toggle Modules</h3>
        </div>
        <form method="POST" action="{{ route('hrm.settings.modules.update') }}">
            @csrf
            <div class="box-body">
                <p class="help-block">Select which modules are enabled for this business. These control visibility/availability across menus and features.</p>

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
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>

    <div class="box box-info" style="margin-top: 20px;">
        <div class="box-header with-border">
            <h3 class="box-title">Payroll Accounting Setup</h3>
        </div>
        <div class="box-body">
            <p class="help-block">Configure the accounts used when payroll is processed and posted to the chart of accounts.</p>
            <a href="{{ route('hrm.settings.payroll.edit') }}" class="btn btn-info">Open Payroll Posting Settings</a>
        </div>
    </div>
</section>
@endsection
