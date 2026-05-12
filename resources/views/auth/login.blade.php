@extends('layouts.auth2')
@section('title', __('lang_v1.login'))
@inject('request', 'Illuminate\Http\Request')
@section('content')
    @php
        $username = old('username');
        $password = null;
        if (config('app.env') == 'demo') {
            $username = 'admin';
            $password = '123456';

            $demo_types = [
                'all_in_one' => 'admin',
                'super_market' => 'admin',
                'pharmacy' => 'admin-pharmacy',
                'electronics' => 'admin-electronics',
                'services' => 'admin-services',
                'restaurant' => 'admin-restaurant',
                'superadmin' => 'superadmin',
                'woocommerce' => 'woocommerce_user',
                'essentials' => 'admin-essentials',
                'manufacturing' => 'manufacturer-demo',
            ];

            if (!empty($_GET['demo_type']) && array_key_exists($_GET['demo_type'], $demo_types)) {
                $username = $demo_types[$_GET['demo_type']];
            }
        }
    @endphp
    <div class="row">
        <div class="col-md-4">
        @if (config('app.env') == 'demo')
        
                @component('components.widget', [
                    'class' => 'box-primary',
                    'header' =>
                        '<h4 class="text-center">'. __('ui.demo_shops') .' <small><i> <br/>'. __('ui.demos_are_for_example_purpose_only_this_application') .' <u>'. __('ui.can_be_used_in_many_other_similar_businesses') .'</u></i> <br/><b>'. __('ui.click_button_to_login_that_business') .'</b></small></h4>',
                ])
                    <a href="?demo_type=all_in_one" class="btn btn-app bg-olive demo-login" data-toggle="tooltip"
                        title="{{ __('ui.showcases_all_feature_available_in_the_application') }}"
                        data-admin="{{ $demo_types['all_in_one'] }}"> <i class="fas fa-star"></i> {{ __('ui.all_in_one') }}</a>

                    <a href="?demo_type=pharmacy" class="btn bg-maroon btn-app demo-login" data-toggle="tooltip"
                        title="{{ __('ui.shops_with_products_having_expiry_dates') }}" data-admin="{{ $demo_types['pharmacy'] }}"><i
                            class="fas fa-medkit"></i>{{ __('ui.pharmacy') }}</a>

                    <a href="?demo_type=services" class="btn bg-orange btn-app demo-login" data-toggle="tooltip"
                        title="{{ __('ui.for_all_service_providers_like_web_development_restaurants_repairing_plumber_salons_beauty_parlors_etc') }}"
                        data-admin="{{ $demo_types['services'] }}"><i class="fas fa-wrench"></i>{{ __('ui.multi_service_center') }}</a>

                    <a href="?demo_type=electronics" class="btn bg-purple btn-app demo-login" data-toggle="tooltip"
                        title="{{ __('ui.products_having_imei_or_serial_number_code') }}" data-admin="{{ $demo_types['electronics'] }}"><i
                            class="fas fa-laptop"></i>{{ __('ui.electronics_mobile_shop') }}</a>

                    <a href="?demo_type=super_market" class="btn bg-navy btn-app demo-login" data-toggle="tooltip"
                        title="{{ __('ui.super_market_similar_kind_of_shops') }}" data-admin="{{ $demo_types['super_market'] }}"><i
                            class="fas fa-shopping-cart"></i> {{ __('ui.super_market') }}</a>

                    <a href="?demo_type=restaurant" class="btn bg-red btn-app demo-login" data-toggle="tooltip"
                        title="{{ __('ui.restaurants_salons_and_other_similar_kind_of_shops') }}"
                        data-admin="{{ $demo_types['restaurant'] }}"><i class="fas fa-utensils"></i> {{ __('ui.restaurant') }}</a>
                    <hr>

                    <i class="icon fas fa-plug"></i> {{ __('ui.premium_optional_modules') }}<br><br>

                    <a href="?demo_type=superadmin" class="btn bg-red-active btn-app demo-login" data-toggle="tooltip"
                        title="{{ __('ui.saas_superadmin_extension_demo') }}" data-admin="{{ $demo_types['superadmin'] }}"><i
                            class="fas fa-university"></i> {{ __('ui.saas_superadmin') }}</a>

                    <a href="?demo_type=woocommerce" class="btn bg-woocommerce btn-app demo-login" data-toggle="tooltip"
                        title="{{ __('ui.woocommerce_demo_user_open_web_shop_in_minutes') }}" style="color:white !important"
                        data-admin="{{ $demo_types['woocommerce'] }}"> <i class="fab fa-wordpress"></i> {{ __('ui.woocommerce') }}</a>

                    <a href="?demo_type=essentials" class="btn bg-navy btn-app demo-login" data-toggle="tooltip"
                        title="{{ __('ui.essentials_hrm_human_resource_management_module_demo') }}" style="color:white !important"
                        data-admin="{{ $demo_types['essentials'] }}">
                        <i class="fas fa-check-circle"></i>
                        {{ __('ui.essentials_hrm') }}</a>

                    <a href="?demo_type=manufacturing" class="btn bg-orange btn-app demo-login" data-toggle="tooltip"
                        title="{{ __('ui.manufacturing_module_demo') }}" style="color:white !important"
                        data-admin="{{ $demo_types['manufacturing'] }}">
                        <i class="fas fa-industry"></i>
                        {{ __('ui.manufacturing_module') }}</a>

                    <a href="?demo_type=superadmin" class="btn bg-maroon btn-app demo-login" data-toggle="tooltip"
                        title="{{ __('ui.project_module_demo') }}" style="color:white !important"
                        data-admin="{{ $demo_types['superadmin'] }}">
                        <i class="fas fa-project-diagram"></i>
                        {{ __('ui.project_module') }}</a>

                    <a href="?demo_type=services" class="btn btn-app demo-login" data-toggle="tooltip"
                        title="{{ __('ui.advance_repair_module_demo') }}" style="color:white !important; background-color: #bc8f8f"
                        data-admin="{{ $demo_types['services'] }}">
                        <i class="fas fa-wrench"></i>
                        {{ __('ui.advance_repair_module') }}</a>

                    <a href="{{ url('docs') }}" target="_blank" class="btn btn-app" data-toggle="tooltip"
                        title="{{ __('ui.connector_module_api_documentation') }}" style="color:white !important; background-color: #2dce89">
                        <i class="fas fa-network-wired"></i>
                        {{ __('ui.connector_module_api_documentation') }}</a>
                @endcomponent
            
            
        
    @endif
        </div>
        <div class="col-md-4">
            <div
                class="tw-p-5 md:tw-p-6 tw-mb-4 tw-rounded-2xl tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm tw-ring-1 tw-ring-gray-200">
                <div class="tw-flex tw-flex-col tw-gap-4 tw-dw-rounded-box tw-dw-p-6 tw-dw-max-w-md">
                    <div class="tw-flex tw-items-center tw-flex-col">
                        <h1 class="tw-text-lg md:tw-text-xl tw-font-semibold tw-text-[#1e1e1e]">
                            @lang('lang_v1.welcome_back')
                        </h1>
                        <h2 class="tw-text-sm tw-font-medium tw-text-gray-500">
                            @lang('lang_v1.login_to_your') {{ config('app.name', 'ultimatePOS') }}
                        </h2>
                    </div>
                    {{-- ✅ Flash message (e.g. session expired) --}}
@if(session('message'))
    <div class="tw-bg-yellow-100 tw-border tw-border-yellow-300 tw-text-yellow-800 tw-px-4 tw-py-3 tw-rounded-lg tw-text-sm tw-font-medium tw-flex tw-justify-between tw-items-center">
        <span>{{ session('message') }}</span>
        <button type="button" onclick="this.parentElement.remove()" class="tw-text-yellow-700 hover:tw-text-yellow-900 font-bold">&times;</button>
    </div>
@endif

                    @if(session('status'))
                        <div class="{{ session('status.success') ? 'tw-bg-green-100 tw-border-green-300 tw-text-green-800' : 'tw-bg-red-100 tw-border-red-300 tw-text-red-800' }} tw-border tw-px-4 tw-py-3 tw-rounded-lg tw-text-sm tw-font-medium">
                            {{ session('status.msg') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" id="login-form">
                        {{ csrf_field() }}
                        @php
                            $otpDeliveryMethod = old('otp_delivery_method', 'sms');
                        @endphp
                        <div class="form-group has-feedback {{ $errors->has('username') ? ' has-error' : '' }}">
                            <label class="tw-dw-form-control">
                                <div class="tw-dw-label">
                                    <span
                                        class="tw-text-xs md:tw-text-sm tw-font-medium tw-text-black">@lang('Username')</span>
                                </div>

                                <input
                                    class="tw-border tw-border-[#D1D5DA] tw-outline-none tw-h-12 tw-bg-transparent tw-rounded-lg tw-px-3 tw-font-medium tw-text-black placeholder:tw-text-gray-500 placeholder:tw-font-medium"
                                    name="username" required autofocus placeholder="@lang('lang_v1.username')"
                                    data-last-active-input="" id="username" type="text" name="username"
                                    value="{{ $username }}" />
                                @if ($errors->has('username'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('username') }}</strong>
                                    </span>
                                @endif
                            </label>
                        </div>

                        <div class="form-group has-feedback {{ $errors->has('password') ? ' has-error' : '' }}">
                            <label class="tw-dw-form-control">
                                <div class="tw-dw-label">
                                    <span
                                        class="tw-text-xs md:tw-text-sm tw-font-medium tw-text-black">@lang('Password')</span>
                                    @if (config('app.env') != 'demo')
                                        <a href="{{ route('password.request') }}"
                                            class="tw-text-xs md:tw-text-sm tw-font-medium tw-bg-gradient-to-r tw-from-indigo-500 tw-to-blue-500 tw-inline-block tw-text-transparent tw-bg-clip-text hover:tw-text-[#467BF5]"
                                            tabindex="-1">@lang('lang_v1.forgot_your_password')</a>
                                    @endif
                                </div>

                                <input
                                    class="tw-border tw-border-[#D1D5DA] tw-outline-none tw-h-12 tw-bg-transparent tw-rounded-lg tw-px-3 tw-font-medium tw-text-black placeholder:tw-text-gray-500 placeholder:tw-font-medium"
                                    id="password" type="password" name="password" value="{{ $password }}" required
                                    placeholder="@lang('lang_v1.password')" />
                                <button type="button" id="show_hide_icon" class="show_hide_icon"
                                    style="position: absolute; top:48px;right:5px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye tw-w-6" viewBox="0 0 24 24" stroke-width="1.5" stroke="#000000" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                        <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                    </svg>
                                </button>
                            </label>
                            @if ($errors->has('password'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </span>
                            @endif
                        </div>

                        <div class="form-group {{ $errors->has('otp_delivery_method') ? ' has-error' : '' }}">
                            <label class="tw-dw-form-control">
                                <div class="tw-dw-label">
                                    <span class="tw-text-xs md:tw-text-sm tw-font-medium tw-text-black">{{ __('ui.send_otp_via') }}</span>
                                </div>
                                <select name="otp_delivery_method" class="tw-border tw-border-[#D1D5DA] tw-outline-none tw-h-12 tw-bg-transparent tw-rounded-lg tw-px-3 tw-font-medium tw-text-black">
                                    <option value="sms" {{ $otpDeliveryMethod === 'sms' ? 'selected' : '' }}>{{ __('ui.sms') }}</option>
                                    <option value="email" {{ $otpDeliveryMethod === 'email' ? 'selected' : '' }}>{{ __('ui.email') }}</option>
                                </select>
                            </label>
                            <p class="tw-text-xs tw-text-gray-500 tw-mt-1">{{ __('ui.choose_how_you_want_to_receive_your_otp') }}</p>
                            @if ($errors->has('otp_delivery_method'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('otp_delivery_method') }}</strong>
                                </span>
                            @endif
                        </div>


                        <div class="tw-dw-form-control">
                            <label class="tw-dw-cursor-pointer tw-dw-label tw-self-start tw-gap-2">
                                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}
                                    class="tw-dw-checkbox">
                                <span
                                    class="tw-text-xs md:tw-text-sm tw-font-medium tw-text-black tw-mt-[0.2rem]">@lang('lang_v1.remember_me')</span>
                            </label>
                        </div>
                        @if(config('constants.enable_recaptcha'))
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="g-recaptcha" data-sitekey="{{ config('constants.google_recaptcha_key') }}"></div>
                                        @if ($errors->has('g-recaptcha-response'))
                                            <span class="text-danger">{{ $errors->first('g-recaptcha-response') }}</span>
                                        @endif
                                </div>  
                            </div>
                        </div>
                        @endif
                        <button type="submit"
                            class="tw-bg-gradient-to-r tw-from-indigo-500 tw-to-blue-500 tw-h-12 tw-rounded-xl tw-text-sm md:tw-text-base tw-text-white tw-font-semibold tw-w-full tw-max-w-full mt-2 hover:tw-from-indigo-600 hover:tw-to-blue-600 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500 focus:tw-ring-offset-2 active:tw-from-indigo-700 active:tw-to-blue-700">
                            @lang('lang_v1.login')
                        </button>
                    </form>

                    <div class="tw-flex tw-items-center tw-flex-col">
                        <!-- Register Url -->

                        @if (!($request->segment(1) == 'business' && $request->segment(2) == 'register'))
                            <!-- Register Url -->
                            @if (config('constants.allow_registration'))
                                <a href="{{ route('business.getRegister') }}@if (!empty(request()->lang)) {{ '?lang=' . request()->lang }} @endif"
                                    class="tw-text-sm tw-font-medium tw-text-gray-500 hover:tw-text-gray-500 tw-mt-2">{{ __('business.not_yet_registered') }}
                                    <span
                                        class="tw-text-sm tw-font-medium tw-bg-gradient-to-r tw-from-indigo-500 tw-to-blue-500 tw-inline-block tw-text-transparent tw-bg-clip-text hover:tw-text-[#467BF5] hover:tw-underline">{{ __('business.register_now') }}</span></a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4"></div>
    </div>

@stop
@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $('#show_hide_icon').off('click');
            $('.change_lang').click(function() {
                window.location = "{{ route('login') }}?lang=" + $(this).attr('value');
            });
            $('a.demo-login').click(function(e) {
                e.preventDefault();
                $('#username').val($(this).data('admin'));
                $('#password').val("{{ $password }}");
                $('form#login-form').submit();
            });

            $('#show_hide_icon').on('click', function(e) {
            e.preventDefault();
            const passwordInput = $('#password');

            if (passwordInput.attr('type') === 'password') {
                passwordInput.attr('type', 'text');
                $('#show_hide_icon').html('<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye-off tw-w-6" viewBox="0 0 24 24" stroke-width="1.5" stroke="#000000" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828"/><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 2.712 -3.678 4.32 -4.674m2.86 -1.146a9.055 9.055 0 0 1 1.82 -.18c3.6 0 6.6 2 9 6c-.666 1.11 -1.379 2.067 -2.138 2.87"/><path d="M3 3l18 18"/></svg>');
            }
            else if (passwordInput.attr('type') === 'text') {
                passwordInput.attr('type', 'password');
                $('#show_hide_icon').html('<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye tw-w-6" viewBox="0 0 24 24" stroke-width="1.5" stroke="#000000" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/></svg>');
            }
        });
        })
    </script>
@endsection
