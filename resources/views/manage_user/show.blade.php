@extends('layouts.app')

@section('title', __( 'lang_v1.view_user' ))

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-4">
                <h3>@lang( 'lang_v1.view_user' )</h3>
            </div>
            <div class="col-md-4 col-xs-12 mt-15 pull-right">
                {!! Form::select('user_id', $users, $user->id , ['class' => 'form-control select2', 'id' => 'user_id']); !!}
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-md-3">
                <!-- Profile Image -->
                <div class="box box-primary">
                    <div class="box-body box-profile">
                        @php
                            if(isset($user->media->display_url)) {
                                $img_src = $user->media->display_url;
                            } else {
                                $img_src = 'https://ui-avatars.com/api/?name='.$user->first_name;
                            }
                        @endphp

                        <img class="profile-user-img img-responsive img-circle" src="{{$img_src}}" alt="User profile picture">

                        <h3 class="profile-username text-center">
                            {{$user->user_full_name}}
                        </h3>

                        <p class="text-muted text-center" title="@lang('user.role')">
                            {{$user->role_name}}
                        </p>

                        <ul class="list-group list-group-unbordered">
                            <li class="list-group-item">
                                <b>@lang( 'business.username' )</b>
                                <a class="pull-right">{{$user->username}}</a>
                            </li>
                            <li class="list-group-item">
                                <b>@lang( 'business.email' )</b>
                                <a class="pull-right">{{$user->email}}</a>
                            </li>
                            <li class="list-group-item">
                                <b>{{ __('lang_v1.status_for_user') }}</b>
                                @if($user->status == 'active')
                                    <span class="label label-success pull-right">
                                        @lang('business.is_active')
                                    </span>
                                @else
                                    <span class="label label-danger pull-right">
                                        @lang('lang_v1.inactive')
                                    </span>
                                @endif
                            </li>
                        </ul>
                        @can('user.update')
                            <a href="{{action([\App\Http\Controllers\ManageUserController::class, 'edit'], [$user->id])}}" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-sm tw-text-white">
                                <i class="glyphicon glyphicon-edit"></i>
                                @lang("messages.edit")
                            </a>
                        @endcan
                        @can('user.otp.toggle')
                            <form action="{{ route('users.toggle_otp', [$user->id]) }}" method="POST" class="otp-toggle-form" style="margin-top: 14px;">
                                @csrf
                                <input type="hidden" name="otp_login_enabled" value="0">
                                <div class="otp-card otp-card--compact">
                                    <div class="otp-card__header">
                                        <div>
                                            <div class="otp-card__label">Two-Factor Authentication</div>
                                            <div class="otp-card__hint">Require OTP after password login</div>
                                        </div>
                                        <label class="otp-switch" title="Toggle OTP login">
                                            <input class="otp-switch__input" type="checkbox" name="otp_login_enabled" value="1" id="otp_login_enabled" {{ !empty($user->otp_login_enabled) ? 'checked' : '' }} {{ empty($user->contact_number) ? 'disabled' : '' }}>
                                            <span class="otp-slider"></span>
                                        </label>
                                    </div>
                                    <div class="otp-card__status {{ !empty($user->otp_login_enabled) ? 'is-on' : 'is-off' }}" id="otp_login_enabled_status">
                                        {{ !empty($user->otp_login_enabled) ? 'Enabled' : 'Disabled' }}
                                    </div>
                                    <div class="otp-card__phone">Phone: {{ !empty($user->contact_number) ? $user->contact_number : 'Not set' }}</div>
                                    @if(empty($user->contact_number))
                                        <small class="text-danger d-block" style="margin-top:6px;">Add a phone number before enabling OTP.</small>
                                    @endif
                                </div>
                            </form>
                        @else
                            <div class="otp-card otp-card--compact" style="margin-top: 14px;">
                                <div class="otp-card__label">Two-Factor Authentication</div>
                                <div class="otp-card__status {{ !empty($user->otp_login_enabled) ? 'is-on' : 'is-off' }}" style="margin-top: 10px;">
                                    {{ !empty($user->otp_login_enabled) ? 'Enabled' : 'Disabled' }}
                                </div>
                            </div>
                        @endcan
                    </div>
                    <!-- /.box-body -->
                </div>
                <!-- /.box -->
            </div>
            <div class="col-md-9">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs nav-justified">
                        <li class="active">
                            <a href="#user_info_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-user" aria-hidden="true"></i> @lang( 'lang_v1.user_info')</a>
                        </li>

                        <li>
                            <a href="#documents_and_notes_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-paperclip" aria-hidden="true"></i> @lang('lang_v1.documents_and_notes')</a>
                        </li>

                        <li>
                            <a href="#activities_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-pen-square" aria-hidden="true"></i> @lang('lang_v1.activities')</a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane active" id="user_info_tab">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="col-md-6">
                                            <p><strong>@lang( 'lang_v1.cmmsn_percent' ): </strong> {{$user->cmmsn_percent}}%</p>
                                    </div>
                                    <div class="col-md-6">
                                        @php
                                            $selected_contacts = ''
                                        @endphp
                                        @if(count($user->contactAccess)) 
                                            @php
                                                $selected_contacts_array = [];
                                            @endphp
                                            @foreach($user->contactAccess as $contact) 
                                                @php
                                                    $selected_contacts_array[] = $contact->name; 
                                                @endphp
                                            @endforeach 
                                            @php
                                                $selected_contacts = implode(', ', $selected_contacts_array);
                                            @endphp
                                        @else 
                                            @php
                                                $selected_contacts = __('lang_v1.all'); 
                                            @endphp
                                        @endif
                                        <p>
                                            <strong>@lang( 'lang_v1.allowed_contacts' ): </strong>
                                                {{$selected_contacts}}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            @include('user.show_details')
                        </div>
                        <div class="tab-pane" id="documents_and_notes_tab">
                            <!-- model id like project_id, user_id -->
                            <input type="hidden" name="notable_id" id="notable_id" value="{{$user->id}}">
                            <!-- model name like App\User -->
                            <input type="hidden" name="notable_type" id="notable_type" value="App\User">
                            <div class="document_note_body">
                            </div>
                        </div>
                        <div class="tab-pane" id="activities_tab">
                            <div class="row">
                                <div class="col-md-12">
                                    @include('activity_log.activities')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>    
@endsection
@section('javascript')
    <!-- document & note.js -->
    @include('documents_and_notes.document_and_note_js')

    <script type="text/javascript">
        $(document).ready( function(){
            function syncOtpStatusLabel() {
                var enabled = $('#otp_login_enabled').is(':checked');
                $('#otp_login_enabled_status')
                    .toggleClass('is-on', enabled)
                    .toggleClass('is-off', !enabled)
                    .text(enabled ? 'Enabled' : 'Disabled');
            }

            syncOtpStatusLabel();

            $(document).on('change', '#otp_login_enabled', function() {
                syncOtpStatusLabel();
                $(this).closest('form').submit();
            });

            $('#user_id').change( function() {
                if ($(this).val()) {

                    window.location = "{{url('/users')}}/" + $(this).val();
                }
            });
        });
    </script>
@endsection

@section('styles')
<style>
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

    .otp-card__status.is-on {
        background: #dcfce7;
        color: #166534;
    }

    .otp-card__status.is-off {
        background: #e5e7eb;
        color: #374151;
    }

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
        content: '';
        height: 22px;
        width: 22px;
        left: 4px;
        top: 4px;
        background-color: white;
        transition: 0.25s;
        border-radius: 50%;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.18);
    }

    .otp-switch__input:checked + .otp-slider {
        background-color: #16a34a;
    }

    .otp-switch__input:checked + .otp-slider:before {
        transform: translateX(24px);
    }

    .otp-switch__input:not(:checked) + .otp-slider {
        background-color: #d1d5db;
    }

    .otp-switch__input:disabled + .otp-slider {
        cursor: not-allowed;
        opacity: 0.6;
    }

    .otp-switch__input:focus-visible + .otp-slider {
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18), inset 0 0 0 1px rgba(15, 23, 42, 0.08);
    }

    @media (max-width: 767px) {
        .otp-card__header {
            align-items: flex-start;
        }
    }
</style>
@endsection