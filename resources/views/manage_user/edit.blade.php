@extends('layouts.app')

@section('title', __( 'user.edit_user' ))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang( 'user.edit_user' )</h1>
</section>

<!-- Main content -->
<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\ManageUserController::class, 'update'], [$user->id]), 'method' => 'PUT', 'id' => 'user_edit_form']) !!}
    <div class="row">
        <div class="col-md-12">
        @component('components.widget', ['class' => 'box-primary'])
            <div class="col-md-2">
                <div class="form-group">
                  {!! Form::label('surname', __( 'business.prefix' ) . ':') !!}
                    {!! Form::text('surname', $user->surname, ['class' => 'form-control', 'placeholder' => __( 'business.prefix_placeholder' ) ]); !!}
                </div>
            </div>
            <div class="col-md-5">
                <div class="form-group">
                  {!! Form::label('first_name', __( 'business.first_name' ) . ':*') !!}
                    {!! Form::text('first_name', $user->first_name, ['class' => 'form-control', 'required', 'placeholder' => __( 'business.first_name' ) ]); !!}
                </div>
            </div>
            <div class="col-md-5">
                <div class="form-group">
                  {!! Form::label('last_name', __( 'business.last_name' ) . ':') !!}
                    {!! Form::text('last_name', $user->last_name, ['class' => 'form-control', 'placeholder' => __( 'business.last_name' ) ]); !!}
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="col-md-4">
                <div class="form-group">
                  {!! Form::label('email', __( 'business.email' ) . ':*') !!}
                    {!! Form::text('email', $user->email, ['class' => 'form-control', 'required', 'placeholder' => __( 'business.email' ) ]); !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                  {!! Form::label('contact_number', 'Phone No:*') !!}
                    {!! Form::text('contact_number', $user->contact_number, ['class' => 'form-control', 'required', 'placeholder' => 'Phone No' ]); !!}
                </div>
            </div>

            @can('user.otp.toggle')
            <div class="col-md-3">
              <div class="otp-card">
                <div class="otp-card__header">
                  <div>
                    <div class="otp-card__label">Two-Factor Authentication</div>
                    <div class="otp-card__hint">Require OTP after password login</div>
                  </div>
                  <label class="otp-switch" title="Toggle OTP login">
                    <input type="checkbox" name="otp_login_enabled" value="1" id="otp_login_enabled" {{ !empty($user->otp_login_enabled) ? 'checked' : '' }} {{ empty($user->contact_number) ? 'disabled' : '' }}>
                    <span class="otp-slider"></span>
                  </label>
                </div>
                <div class="otp-card__status {{ !empty($user->otp_login_enabled) ? 'is-on' : 'is-off' }}" id="otp_login_enabled_status">{{ !empty($user->otp_login_enabled) ? 'Enabled' : 'Disabled' }}</div>
                <div class="otp-card__phone">Phone: {{ !empty($user->contact_number) ? $user->contact_number : 'Not set' }}</div>
                @if(empty($user->contact_number))
                  <small class="text-danger d-block" style="margin-top:6px;">Add a phone number before enabling OTP.</small>
                @endif
              </div>
            </div>
            @endcan

            <div class="col-md-3">
              <div class="form-group">
                <div class="checkbox">
                  <br/>
                  <label>
                       {!! Form::checkbox('is_enable_service_staff_pin', 1, $user->is_enable_service_staff_pin, ['class' => 'input-icheck status', 'id' => 'is_enable_service_staff_pin']); !!} {{ __('lang_v1.enable_service_staff_pin') }}
                  </label>
                  @show_tooltip(__('lang_v1.tooltip_is_enable_service_staff_pin'))
                </div>
              </div>
            </div>
            <div class="col-md-2 service_staff_pin_div {{ $user->is_enable_service_staff_pin == 1 ? '' : 'hide' }}">
              <div class="form-group">
                {!! Form::label('service_staff_pin', __( 'lang_v1.staff_pin' ) . ':') !!}
                  {!! Form::password('service_staff_pin', ['class' => 'form-control','placeholder' => __( 'lang_v1.staff_pin' ) ]); !!}
              </div>
            </div>
        @endcomponent
        </div>
        <div class="col-md-12">
        @component('components.widget', ['title' => __('lang_v1.roles_and_permissions')])
            <div class="col-md-4">
                <div class="form-group">
                    <div class="checkbox">
                      <label>
                        {!! Form::checkbox('allow_login', 1, !empty($user->allow_login), 
                        [ 'class' => 'input-icheck', 'id' => 'allow_login']); !!} {{ __( 'lang_v1.allow_login' ) }}
                      </label>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="user_auth_fields @if(empty($user->allow_login)) hide @endif">
            @if(empty($user->allow_login))
                <div class="col-md-4">
                    <div class="form-group">
                      {!! Form::label('username', __( 'business.username' ) . ':') !!}
                      @if(!empty($username_ext))
                        <div class="input-group">
                          {!! Form::text('username', null, ['class' => 'form-control', 'placeholder' => __( 'business.username' ) ]); !!}
                          <span class="input-group-addon">{{$username_ext}}</span>
                        </div>
                        <p class="help-block" id="show_username"></p>
                      @else
                          {!! Form::text('username', null, ['class' => 'form-control', 'placeholder' => __( 'business.username' ) ]); !!}
                      @endif
                      <p class="help-block">@lang('lang_v1.username_help')</p>
                    </div>
                </div>
            @endif
            @can('user.password.reset')
            <div class="col-md-4">
                <div class="form-group">
                  {!! Form::label('password', __( 'business.password' ) . ':') !!}
                    {!! Form::password('password', ['class' => 'form-control', 'placeholder' => __( 'business.password'), 'required' => empty($user->allow_login) ? true : false ]); !!}
                    <p class="help-block">@lang('user.leave_password_blank')</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                  {!! Form::label('confirm_password', __( 'business.confirm_password' ) . ':') !!}
                    {!! Form::password('confirm_password', ['class' => 'form-control', 'placeholder' => __( 'business.confirm_password' ), 'required' => empty($user->allow_login) ? true : false ]); !!}
                  
                </div>
            </div>
            @else
            <div class="col-md-8">
                <div class="alert alert-info" style="margin-top: 24px;">You do not have permission to reset this user's password.</div>
            </div>
            @endcan
            </div>
            <div class="clearfix"></div>
            <div class="col-md-6">
                <div class="form-group">
                  {!! Form::label('role', __( 'user.role' ) . ':*') !!} @show_tooltip(__('lang_v1.admin_role_location_permission_help'))
                    {!! Form::select('role', $roles, !empty($user->roles->first()->id) ? $user->roles->first()->id : null, ['class' => 'form-control select2', 'style' => 'width: 100%;']); !!}
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="col-md-3">
                <h4>@lang( 'role.access_locations' ) @show_tooltip(__('tooltip.access_locations_permission'))</h4>
            </div>
            <div class="col-md-9">
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                          {!! Form::checkbox('access_all_locations', 'access_all_locations', !is_array($permitted_locations) && $permitted_locations == 'all', 
                        [ 'class' => 'input-icheck']); !!} {{ __( 'role.all_locations' ) }} 
                        </label>
                        @show_tooltip(__('tooltip.all_location_permission'))
                    </div>
                  </div>
              @foreach($locations as $location)
                <div class="col-md-12">
                    <div class="checkbox">
                      <label>
                        {!! Form::checkbox('location_permissions[]', 'location.' . $location->id, is_array($permitted_locations) && in_array($location->id, $permitted_locations), 
                        [ 'class' => 'input-icheck']); !!} {{ $location->name }} @if(!empty($location->location_id))({{ $location->location_id}}) @endif
                      </label>
                    </div>
                </div>
              @endforeach
            </div>
        @endcomponent
        </div>

        <div class="col-md-12">
            @component('components.widget', ['title' => __('sale.sells')])

            <div class="col-md-4">
                <div class="form-group">
                  {!! Form::label('cmmsn_percent', __( 'lang_v1.cmmsn_percent' ) . ':') !!} @show_tooltip(__('lang_v1.commsn_percent_help'))
                    {!! Form::text('cmmsn_percent', !empty($user->cmmsn_percent) ? @num_format($user->cmmsn_percent) : 0, ['class' => 'form-control input_number', 'placeholder' => __( 'lang_v1.cmmsn_percent' )]); !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                  {!! Form::label('max_sales_discount_percent', __( 'lang_v1.max_sales_discount_percent' ) . ':') !!} @show_tooltip(__('lang_v1.max_sales_discount_percent_help'))
                    {!! Form::text('max_sales_discount_percent', !is_null($user->max_sales_discount_percent) ? @num_format($user->max_sales_discount_percent) : null, ['class' => 'form-control input_number', 'placeholder' => __( 'lang_v1.max_sales_discount_percent' ) ]); !!}
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="col-md-2">
                <div class="form-group">
                  <div class="checkbox">
                    <br>
                    <label>
                         {!! Form::checkbox('is_active', $user->status, $is_checked_checkbox, ['class' => 'input-icheck status']); !!} {{ __('lang_v1.status_for_user') }}
                    </label>
                    @show_tooltip(__('lang_v1.tooltip_enable_user_active'))
                  </div>
                </div>
            </div>
            
            <div class="col-sm-4 selected_contacts_div @if(!$user->selected_contacts) hide @endif">
                <div class="form-group">
                  {!! Form::label('user_allowed_contacts', __('lang_v1.selected_contacts') . ':') !!}
                    <div class="form-group">
                      {!! Form::select('selected_contact_ids[]', $contact_access, array_keys($contact_access), ['class' => 'form-control select2', 'multiple', 'style' => 'width: 100%;', 'id' => 'user_allowed_contacts' ]); !!}
                    </div>
                </div>
            </div>
            @endcomponent
        </div>
    </div>
    @include('user.edit_profile_form_part', ['bank_details' => !empty($user->bank_details) ? json_decode($user->bank_details, true) : null])

    @if(!empty($form_partials))
      @foreach($form_partials as $partial)
        {!! $partial !!}
      @endforeach
    @endif
    <div class="row">
        <div class="col-md-12 text-center">
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white" id="submit_user_button">@lang( 'messages.update' )</button>
        </div>
    </div>
    {!! Form::close() !!}
  @stop
  @section('styles')
  <style>
    .otp-card {
      padding: 14px 16px;
      border: 1px solid #dbe2ea;
      border-radius: 14px;
      background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
      min-height: 120px;
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
      font-size: 0.95rem;
    }

    .otp-card__hint,
    .otp-card__phone {
      color: #6b7280;
      font-size: 0.85rem;
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

    .otp-switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .otp-slider {
      position: absolute;
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

    .otp-switch input:checked + .otp-slider {
      background-color: #2563eb;
    }

    .otp-switch input:checked + .otp-slider:before {
      transform: translateX(24px);
    }

    .otp-switch input:disabled + .otp-slider {
      cursor: not-allowed;
      opacity: 0.6;
    }
  </style>
  @endsection
@section('javascript')
<script type="text/javascript">
  $(document).ready(function(){
    __page_leave_confirmation('#user_edit_form');

      function syncOtpStatusLabel() {
        var enabled = $('#otp_login_enabled').is(':checked');
        $('#otp_login_enabled_status')
          .toggleClass('is-on', enabled)
          .toggleClass('is-off', !enabled)
          .text(enabled ? 'Enabled' : 'Disabled');
      }

      syncOtpStatusLabel();

      $('#otp_login_enabled').on('change', function() {
        syncOtpStatusLabel();
      });
    
    $('#selected_contacts').on('ifChecked', function(event){
      $('div.selected_contacts_div').removeClass('hide');
    });
    $('#selected_contacts').on('ifUnchecked', function(event){
      $('div.selected_contacts_div').addClass('hide');
    });

    $('#is_enable_service_staff_pin').on('ifChecked', function(event){
      $('div.service_staff_pin_div').removeClass('hide');
    });

    $('#is_enable_service_staff_pin').on('ifUnchecked', function(event){
      $('div.service_staff_pin_div').addClass('hide');
      $('#service_staff_pin').val('');
    });

    $('#allow_login').on('ifChecked', function(event){
      $('div.user_auth_fields').removeClass('hide');
    });
    $('#allow_login').on('ifUnchecked', function(event){
      $('div.user_auth_fields').addClass('hide');
    });

    $('#user_allowed_contacts').select2({
        ajax: {
            url: '/contacts/customers',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term, // search term
                    page: params.page,
                    all_contact: true
                };
            },
            processResults: function(data) {
                return {
                    results: data,
                };
            },
        },
        templateResult: function (data) { 
            var template = '';
            if (data.supplier_business_name) {
                template += data.supplier_business_name + "<br>";
            }
            template += data.text + "<br>" + LANG.mobile + ": " + data.mobile;

            return  template;
        },
        minimumInputLength: 1,
        escapeMarkup: function(markup) {
            return markup;
        },
    });
  });
  // Add client PIN validator
  $.validator.addMethod('pin_format', function(value, element) {
      return this.optional(element) || /^[A-Za-z][A-Za-z0-9]*[A-Za-z]$/.test(value);
  }, 'Must start and end with a letter and contain only letters/numbers.');

  $('form#user_edit_form').validate({
                rules: {
                    first_name: {
                        required: true,
                    },
                    email: {
                        email: true,
                        remote: {
                            url: "/business/register/check-email",
                            type: "post",
                            data: {
                                email: function() {
                                    return $( "#email" ).val();
                                },
                                user_id: {{$user->id}}
                            }
                        }
                    },
                    password: {
                        minlength: 5
                    },
                    confirm_password: {
                        equalTo: "#password",
                    },
                    username: {
                        minlength: 5,
                        remote: {
                            url: "/business/register/check-username",
                            type: "post",
                            data: {
                                username: function() {
                                    return $( "#username" ).val();
                                },
                                @if(!empty($username_ext))
                                  username_ext: "{{$username_ext}}"
                                @endif
                            }
                        }
                    }
          ,
          client_pin: {
            pin_format: true
          }
                },
                messages: {
                    password: {
                        minlength: 'Password should be minimum 5 characters',
                    },
                    confirm_password: {
                        equalTo: 'Should be same as password'
                    },
                    username: {
                        remote: 'Invalid username or User already exist'
                    },
                    email: {
                        remote: '{{ __("validation.unique", ["attribute" => __("business.email")]) }}'
                    }
                }
            });
</script>
@endsection