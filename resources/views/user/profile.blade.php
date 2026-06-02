@extends('layouts.app')
@section('title', __('lang_v1.my_profile'))

@section('css')
<style>
/* Module toggle switch */
.module-switch { position: relative; display: inline-block; width: 46px; height: 24px; }
.module-switch input { opacity: 0; width: 0; height: 0; }
.module-slider {
    position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
    background-color: #ccc; border-radius: 24px; transition: .3s;
}
.module-slider:before {
    position: absolute; content: ""; height: 18px; width: 18px;
    left: 3px; bottom: 3px; background-color: white; border-radius: 50%; transition: .3s;
}
.module-switch input:checked + .module-slider { background-color: #3490dc; }
.module-switch input:checked + .module-slider:before { transform: translateX(22px); }
.module-switch input:disabled + .module-slider { opacity: 0.5; cursor: not-allowed; }
.module-card {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 14px; margin-bottom: 8px;
    border: 1px solid #e2e8f0; border-radius: 8px; background: #f9fafb;
}
.module-card .module-name { font-weight: 600; font-size: 14px; color: #374151; }
.module-card .module-status { font-size: 11px; color: #6b7280; margin-left: 6px; }

.otp-card--compact {
    padding: 12px 14px;
    border: 1px solid #dbe2ea;
    border-radius: 14px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.otp-card__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.otp-card__label {
    font-weight: 700;
    color: #111827;
    font-size: 0.92rem;
}

.otp-card__hint,
.otp-card__phone {
    color: #6b7280;
    font-size: 0.82rem;
    margin-top: 3px;
}

.otp-card__status {
    display: inline-flex;
    align-items: center;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 700;
    margin-top: 10px;
}

.otp-card__status.is-on { background: #dcfce7; color: #166534; }
.otp-card__status.is-off { background: #e5e7eb; color: #374151; }

.otp-switch {
    position: relative;
    display: inline-block;
    width: 54px;
    height: 30px;
    margin: 0;
    flex: 0 0 auto;
}

.otp-switch__input {
    position: absolute;
    width: 1px;
    height: 1px;
    margin: -1px;
    padding: 0;
    clip: rect(0, 0, 0, 0);
    clip-path: inset(50%);
    overflow: hidden;
    white-space: nowrap;
    -webkit-appearance: none;
    appearance: none;
    border: 0;
    background: transparent;
    cursor: pointer;
    opacity: 0;
}

.otp-slider {
    position: absolute;
    display: block;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e1;
    transition: 0.25s;
    border-radius: 999px;
    box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.08);
}

.otp-slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 4px;
    top: 4px;
    background-color: white;
    transition: 0.25s;
    border-radius: 50%;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.18);
}

.otp-switch__input:checked + .otp-slider { background-color: #16a34a; }
.otp-switch__input:checked + .otp-slider:before { transform: translateX(24px); }
.otp-switch__input:not(:checked) + .otp-slider { background-color: #d1d5db; }

@media (max-width: 767px) {
    .otp-card__header {
        align-items: flex-start;
    }
}
</style>
@endsection

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.my_profile')</h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
{!! Form::open(['url' => action([\App\Http\Controllers\UserController::class, 'updatePassword']), 'method' => 'post', 'id' => 'edit_password_form',
            'class' => 'form-horizontal' ]) !!}
<div class="row">
    <div class="col-sm-12">
        <div class="box box-solid"> <!--business info box start-->
            <div class="box-header">
                <div class="box-header">
                    <h3 class="box-title"> @lang('user.change_password')</h3>
                </div>
            </div>
            <div class="box-body">
                <div class="form-group">
                    {!! Form::label('current_password', __('user.current_password') . ':', ['class' => 'col-sm-3 control-label']) !!}
                    <div class="col-sm-9">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-lock"></i>
                            </span>
                            {!! Form::password('current_password', ['class' => 'form-control','placeholder' => __('user.current_password'), 'required']); !!}
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('new_password', __('user.new_password') . ':', ['class' => 'col-sm-3 control-label']) !!}
                    <div class="col-sm-9">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-lock"></i>
                            </span>
                            {!! Form::password('new_password', ['class' => 'form-control','placeholder' => __('user.new_password'), 'required']); !!}
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('confirm_password', __('user.confirm_new_password') . ':', ['class' => 'col-sm-3 control-label']) !!}
                    <div class="col-sm-9">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-lock"></i>
                            </span>
                            {!! Form::password('confirm_password', ['class' => 'form-control','placeholder' =>  __('user.confirm_new_password'), 'required']); !!}
                        </div>
                    </div>
                </div>
                <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white pull-right">@lang('messages.update')</button>
            </div>
        </div>
    </div>
</div>
{!! Form::close() !!}
{!! Form::open(['url' => action([\App\Http\Controllers\UserController::class, 'updateProfile']), 'method' => 'post', 'id' => 'edit_user_profile_form', 'files' => true ]) !!}
<div class="row">
    <div class="col-sm-8">
        <div class="box box-solid"> <!--business info box start-->
            <div class="box-header">
                <div class="box-header">
                    <h3 class="box-title"> @lang('user.edit_profile')</h3>
                </div>
            </div>
            <div class="box-body">
                <div class="form-group col-md-2">
                    {!! Form::label('surname', __('business.prefix') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-info"></i>
                        </span>
                        {!! Form::text('surname', $user->surname, ['class' => 'form-control','placeholder' => __('business.prefix_placeholder')]); !!}
                    </div>
                </div>
                <div class="form-group col-md-5">
                    {!! Form::label('first_name', __('business.first_name') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-info"></i>
                        </span>
                        {!! Form::text('first_name', $user->first_name, ['class' => 'form-control','placeholder' => __('business.first_name'), 'required']); !!}
                    </div>
                </div>
                <div class="form-group col-md-5">
                    {!! Form::label('last_name', __('business.last_name') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-info"></i>
                        </span>
                        {!! Form::text('last_name', $user->last_name, ['class' => 'form-control','placeholder' => __('business.last_name')]); !!}
                    </div>
                </div>
                <div class="form-group col-md-6">
                    {!! Form::label('email', __('business.email') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-info"></i>
                        </span>
                        {!! Form::email('email',  $user->email, ['class' => 'form-control','placeholder' => __('business.email') ]); !!}
                    </div>
                </div>
                <div class="form-group col-md-6">
                    {!! Form::label('language', __('business.language') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-info"></i>
                        </span>
                        {!! Form::select('language',$languages, $user->language, ['class' => 'form-control select2']); !!}
                    </div>
                </div>
                <div class="form-group col-md-6">
                    {!! Form::label('contact_number', __('contact.contact') . ' ' . __('lang_v1.contact_no') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-phone"></i>
                        </span>
                        {!! Form::text('contact_number', $user->contact_number, ['class' => 'form-control', 'placeholder' => __('lang_v1.contact_no')]); !!}
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <div class="otp-card otp-card--compact" style="margin-top: 24px;">
                        <div class="otp-card__header">
                            <div>
                                <div class="otp-card__label">Two-Factor Authentication</div>
                                <div class="otp-card__hint">Require OTP after password login</div>
                            </div>
                            <label class="otp-switch" title="Toggle OTP login">
                                <input type="hidden" name="otp_login_enabled" value="0">
                                <input class="otp-switch__input" type="checkbox" name="otp_login_enabled" value="1" id="otp_login_enabled" {{ !empty($user->otp_login_enabled) ? 'checked' : '' }}>
                                <span class="otp-slider"></span>
                            </label>
                        </div>
                        <div class="otp-card__status {{ !empty($user->otp_login_enabled) ? 'is-on' : 'is-off' }}" id="otp_login_enabled_status">
                            {{ !empty($user->otp_login_enabled) ? 'Enabled' : 'Disabled' }}
                        </div>
                        <div class="otp-card__phone">Phone: {{ !empty($user->contact_number) ? $user->contact_number : 'Not set' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        @component('components.widget', ['title' => __('lang_v1.profile_photo')])
            @if(!empty($user->media))
                <div class="col-md-12 text-center">
                    {!! $user->media->thumbnail([150, 150], 'img-circle') !!}
                </div>
            @endif
            <div class="col-md-12">
                <div class="form-group">
                    {!! Form::label('profile_photo', __('lang_v1.upload_image') . ':') !!}
                    {!! Form::file('profile_photo', ['id' => 'profile_photo', 'accept' => 'image/*']); !!}
                    <small><p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])</p></small>
                </div>
            </div>
        @endcomponent
    </div>
@include('user.edit_profile_form_part', ['bank_details' => !empty($user->bank_details) ? json_decode($user->bank_details, true) : null])
<div class="row">
    <div class="col-md-12 text-center">
        <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-lg">@lang('messages.update')</button>
    </div>
</div>
{!! Form::close() !!}

@if(auth()->user()->isSuperAdmin() && auth()->user()->can('manage_modules') && !empty($modules))
<div class="row" style="margin-top: 20px;">
    <div class="col-sm-12">
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title"><i class="fa fa-puzzle-piece" style="margin-right:6px;"></i>Module Management</h3>
                <small class="text-muted" style="margin-left:10px;">Toggle modules on/off instantly</small>
            </div>
            <div class="box-body">
                <div class="row">
                    @foreach($modules as $module)
                    <div class="col-sm-6 col-md-4">
                        <div class="module-card">
                            <div>
                                <span class="module-name">{{ $module['name'] }}</span>
                                <span class="module-status" id="status_{{ $module['name'] }}">
                                    {{ $module['enabled'] ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                            <label class="module-switch">
                                <input type="checkbox"
                                    class="module-toggle-cb"
                                    id="toggle_{{ $module['name'] }}"
                                    data-module="{{ $module['name'] }}"
                                    {{ $module['enabled'] ? 'checked' : '' }}>
                                <span class="module-slider"></span>
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

</section>
<!-- /.content -->
@endsection

@section('javascript')
<script>
function syncOtpStatusLabel() {
    if (!$('#otp_login_enabled').length) {
        return;
    }

    var enabled = $('#otp_login_enabled').is(':checked');
    $('#otp_login_enabled_status')
        .toggleClass('is-on', enabled)
        .toggleClass('is-off', !enabled)
        .text(enabled ? 'Enabled' : 'Disabled');
}

$(document).on('change', '#otp_login_enabled', function() {
    syncOtpStatusLabel();
});

syncOtpStatusLabel();

$(document).on('change', '.module-toggle-cb', function() {
    var cb       = $(this);
    var modName  = cb.data('module');
    var enable   = cb.is(':checked');
    var action   = enable ? 'activate' : 'deactivate';
    var statusEl = $('#status_' + modName);

    cb.prop('disabled', true);

    $.ajax({
        url: '/manage-modules/' + encodeURIComponent(modName),
        type: 'POST',
        data: {
            _method: 'PUT',
            _token: $('meta[name="csrf-token"]').attr('content'),
            action_type: action
        },
        success: function(response) {
            if (response.success) {
                statusEl.text(enable ? 'Enabled' : 'Disabled');
                toastr.success(modName + ' ' + (enable ? 'enabled' : 'disabled') + ' successfully.');
            } else {
                cb.prop('checked', !enable);
                toastr.error(response.msg || 'Failed to update module.');
            }
        },
        error: function() {
            cb.prop('checked', !enable);
            toastr.error('Error updating module. Please try again.');
        },
        complete: function() {
            cb.prop('disabled', false);
        }
    });
});
</script>
@endsection