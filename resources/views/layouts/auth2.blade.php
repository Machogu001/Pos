<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    @php
        $faviconPath = 'favicon-bremac360.ico';
        $faviconVersion = file_exists(public_path($faviconPath)) ? filemtime(public_path($faviconPath)) : time();
        $faviconPngPath = 'pwa-icons/favicon-bremac360.png';
        $faviconPngVersion = file_exists(public_path($faviconPngPath)) ? filemtime(public_path($faviconPngPath)) : $faviconVersion;
        $faviconLightPath = 'pwa-icons/favicon-bremac360-light.png';
        $faviconLightVersion = file_exists(public_path($faviconLightPath)) ? filemtime(public_path($faviconLightPath)) : $faviconVersion;
        $faviconDarkPath = 'pwa-icons/favicon-bremac360-dark.png';
        $faviconDarkVersion = file_exists(public_path($faviconDarkPath)) ? filemtime(public_path($faviconDarkPath)) : $faviconVersion;
    @endphp
    <link rel="icon" type="image/png" href="{{ asset($faviconPngPath) }}?v={{ $faviconPngVersion }}">
    <link rel="icon" type="image/png" href="{{ asset($faviconLightPath) }}?v={{ $faviconLightVersion }}" media="(prefers-color-scheme: light)">
    <link rel="icon" type="image/png" href="{{ asset($faviconDarkPath) }}?v={{ $faviconDarkVersion }}" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="{{ asset($faviconPath) }}?v={{ $faviconVersion }}" sizes="any">
    <link rel="shortcut icon" href="{{ asset($faviconPath) }}?v={{ $faviconVersion }}" type="image/x-icon">
    @php
        $manifestUrl = url('manifest.webmanifest?v=' . time());
        $iconPath = 'pwa-icons/mobile-app-192.png';
        $iconVersion = file_exists(public_path($iconPath)) ? filemtime(public_path($iconPath)) : time();
    @endphp
    <link rel="manifest" href="{{ $manifestUrl }}">
    <meta name="theme-color" content="#2b6cb0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'POS') }}">
    @if(file_exists(public_path($iconPath)))
        <link rel="apple-touch-icon" sizes="192x192" href="{{ asset($iconPath) }}?v={{ $iconVersion }}">
    @endif
    <link rel="apple-touch-icon" href="{{ asset($iconPath) }}?v={{ $iconVersion }}">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - {{ config('app.name', 'POS') }}</title>

    @include('layouts.partials.css')

    @include('layouts.partials.extracss_auth')

    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <script src='https://www.google.com/recaptcha/api.js'></script>

</head>

<body class="pace-done" data-new-gr-c-s-check-loaded="14.1172.0" data-gr-ext-installed="" cz-shortcut-listen="true">
    @inject('request', 'Illuminate\Http\Request')
    @if (session('status') && session('status.success'))
        <input type="hidden" id="status_span" data-status="{{ session('status.success') }}"
            data-msg="{{ session('status.msg') }}">
    @endif
    <div class="container-fluid">
        <div class="row eq-height-row">
            <div class="col-md-12 col-sm-12 col-xs-12 right-col tw-pt-20 tw-pb-10 tw-px-5">
                <div class="row">
                    <div
                        class="lg:tw-w-16 md:tw-h-16 tw-w-12 tw-h-12 tw-flex tw-items-center tw-justify-center tw-mx-auto tw-overflow-hidden tw-bg-white tw-rounded-full tw-p-0.5 tw-mb-4">
                        <img src="{{ asset('img/logo-small.png')}}" alt="lock" class="tw-rounded-full tw-object-fill" />
                    </div>

                    <div class="tw-absolute tw-top-2 md:tw-top-5 tw-left-4 md:tw-left-8 tw-flex tw-items-center tw-gap-4"
                        style="text-align: left">
                        @include('layouts.partials.language_btn')

                        @if(Route::has('repair-status'))
                            <a class="tw-text-white tw-font-medium tw-text-sm md:tw-text-base hover:tw-text-white"
                                href="{{ action([\Modules\Repair\Http\Controllers\CustomerRepairStatusController::class, 'index']) }}">
                                @lang('repair::lang.repair_status')
                            </a>
                        @endif
                    </div>

                    <div class="tw-absolute tw-top-5 md:tw-top-8 tw-right-5 md:tw-right-10 tw-flex tw-items-center tw-gap-4"
                        style="text-align: left">
                        @if (!($request->segment(1) == 'business' && $request->segment(2) == 'register'))
                            <!-- Register Url -->
                            @if (config('constants.allow_registration'))
                            {{-- <span
                                class="tw-text-white tw-font-medium tw-text-sm md:tw-text-base">{{ __('business.not_yet_registered') }}
                            </span> --}}

                            <div class="tw-border-2 tw-border-white tw-rounded-full tw-h-10 md:tw-h-12 tw-w-24 tw-flex tw-items-center tw-justify-center">
                             <a href="{{ route('business.getRegister')}}@if(!empty(request()->lang)){{'?lang='.request()->lang}}@endif"
                                    class="tw-text-white tw-font-medium tw-text-sm md:tw-text-base hover:tw-text-white">
                                    {{ __('business.register') }}</a>
                            </div>

                                <!-- pricing url -->
                                @if (Route::has('pricing') && config('app.env') != 'demo' && $request->segment(1) != 'pricing')
                                    &nbsp; <a class="tw-text-white tw-font-medium tw-text-sm md:tw-text-base hover:tw-text-white"
                                        href="{{ action([\Modules\Superadmin\Http\Controllers\PricingController::class, 'index']) }}">@lang('superadmin::lang.pricing')</a>
                                @endif
                            @endif
                        @endif
                        @if ($request->segment(1) != 'login')
                            <a class="tw-text-white tw-font-medium tw-text-sm md:tw-text-base hover:tw-text-white"
                                href="{{ action([\App\Http\Controllers\Auth\LoginController::class, 'login'])}}@if(!empty(request()->lang)){{'?lang='.request()->lang}}@endif">{{ __('business.sign_in') }}</a>
                        @endif
                    </div>
                    <div class="col-md-10 col-xs-8" style="text-align: right;">

                    </div>
                </div>
                @yield('content')
            </div>
        </div>
    </div>


    @include('layouts.partials.javascripts')

    <!-- Scripts -->
    <script src="{{ asset('js/login.js?v=' . $asset_v) }}"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            $('.select2_register').select2();

            // $('input').iCheck({
            //     checkboxClass: 'icheckbox_square-blue',
            //     radioClass: 'iradio_square-blue',
            //     increaseArea: '20%' // optional
            // });
        });
    </script>
    <style>
        .wizard>.content {
            background-color: white !important;
        }
    </style>
    @include('layouts.partials.install_prompt')
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('{{ url("service-worker.js") }}?v={{ $asset_v }}')
                    .then(function() {})
                    .catch(function() {});
            });
        }
    </script>
</body>

</html>
