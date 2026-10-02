@inject('request', 'Illuminate\Http\Request')

@if ($request->segment(1) == 'pos' && ($request->segment(2) == 'create' || $request->segment(3) == 'edit'))
    @php
        $pos_layout = true;
    @endphp
@else
    @php
        $pos_layout = false;
    @endphp
@endif

@php
$whitelist = ['127.0.0.1', '::1'];
$faviconPath = 'favicon-bremac360.ico';
$faviconVersion = file_exists(public_path($faviconPath)) ? filemtime(public_path($faviconPath)) : time();
$faviconPngPath = 'pwa-icons/favicon-bremac360.png';
$faviconPngVersion = file_exists(public_path($faviconPngPath)) ? filemtime(public_path($faviconPngPath)) : $faviconVersion;
$faviconLightPath = 'pwa-icons/favicon-bremac360-light.png';
$faviconLightVersion = file_exists(public_path($faviconLightPath)) ? filemtime(public_path($faviconLightPath)) : $faviconVersion;
$faviconDarkPath = 'pwa-icons/favicon-bremac360-dark.png';
$faviconDarkVersion = file_exists(public_path($faviconDarkPath)) ? filemtime(public_path($faviconDarkPath)) : $faviconVersion;
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}"
    dir="{{ in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl')) ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=yes">

    <link rel="icon" type="image/png" href="{{ asset($faviconPngPath) }}?v={{ $faviconPngVersion }}">
    <link rel="icon" type="image/png" href="{{ asset($faviconLightPath) }}?v={{ $faviconLightVersion }}" media="(prefers-color-scheme: light)">
    <link rel="icon" type="image/png" href="{{ asset($faviconDarkPath) }}?v={{ $faviconDarkVersion }}" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="{{ asset($faviconPath) }}?v={{ $faviconVersion }}" sizes="any">
    <link rel="shortcut icon" href="{{ asset($faviconPath) }}?v={{ $faviconVersion }}" type="image/x-icon">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- is_admin() --}}
    <meta name="is_admin" content="{{ is_admin() }}">

    <title>@yield('title') - {{ Session::get('business.name') }}</title>

    @include('accounting::layouts.partials.css')

    {{-- Vue cdn --}}
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14"></script>

    @yield('css')
</head>

<body class="tw-font-sans tw-antialiased tw-text-gray-900 tw-bg-gray-100 @if ($pos_layout) hold-transition lockscreen @else hold-transition skin-@if (!empty(session('business.theme_color'))){{ session('business.theme_color') }}@else{{ 'blue-light' }} @endif sidebar-mini @endif">
    <div class="tw-flex">
        <script type="text/javascript">
            if (localStorage.getItem("upos_sidebar_collapse") == 'true') {
                var body = document.getElementsByTagName("body")[0];
                body.className += " sidebar-collapse";
            }
        </script>
        @if (!$pos_layout)
            @include('layouts.partials.sidebar')
        @else
            @include('layouts.partials.header-pos')
        @endif

        @if (in_array($_SERVER['REMOTE_ADDR'], $whitelist))
            <input type="hidden" id="__is_localhost" value="true">
        @endif

        <!-- Add currency related field-->
        <input type="hidden" id="__code" value="{{ session('currency')['code'] }}">
        <input type="hidden" id="__symbol" value="{{ session('currency')['symbol'] }}">
        <input type="hidden" id="__thousand" value="{{ session('currency')['thousand_separator'] }}">
        <input type="hidden" id="__decimal" value="{{ session('currency')['decimal_separator'] }}">
        <input type="hidden" id="__symbol_placement" value="{{ session('business.currency_symbol_placement') }}">
        <input type="hidden" id="__precision" value="{{ config('constants.currency_precision', 2) }}">
        <input type="hidden" id="__quantity_precision" value="{{ config('constants.quantity_precision', 2) }}">
        <!-- End of currency related field-->

        @can('view_export_buttons')
            <input type="hidden" id="view_export_buttons">
        @endcan

        <main class="tw-flex tw-flex-col tw-flex-1 tw-h-full tw-min-w-0 tw-bg-gray-100">

            @if (!$pos_layout)
                @include('layouts.partials.header')
            @endif

            <!-- empty div for vuejs -->
            <div id="app">
                @yield('vue')
            </div>

            <div class="tw-flex-1 tw-min-h-0 tw-overflow-y-auto" id="scrollable-container">

                @include('accounting::layouts.partials.alert-feedback')

                @yield('content')

                <div class='scrolltop no-print'>
                    <div class='scroll icon'><i class="fas fa-angle-up"></i></div>
                </div>

                @if (config('constants.iraqi_selling_price_adjustment'))
                    <input type="hidden" id="iraqi_selling_price_adjustment">
                @endif

                <!-- This will be printed -->
                <section class="invoice print_section" id="receipt_section">
                </section>

                @if (!$pos_layout)
                    @include('layouts.partials.footer')
                @else
                    @include('layouts.partials.footer_pos')
                @endif

            </div>

        </main>

        @include('home.todays_profit_modal')

    </div>

    @if (!empty($__additional_html))
        {!! $__additional_html !!}
    @endif

    <script>
        (function () {
            var ignoredPhrases = [
                'A listener indicated an asynchronous response by returning true',
                'message channel closed before a response was received'
            ];

            function isKnownExtensionMessage(value) {
                var text = String(value || '');
                return ignoredPhrases.some(function (phrase) {
                    return text.indexOf(phrase) !== -1;
                });
            }

            window.addEventListener('error', function (event) {
                if (isKnownExtensionMessage(event && event.message)) {
                    event.preventDefault();
                }
            });

            window.addEventListener('unhandledrejection', function (event) {
                var reason = event ? event.reason : '';
                var message = reason && reason.message ? reason.message : reason;

                if (isKnownExtensionMessage(message)) {
                    event.preventDefault();
                }
            });
        })();
    </script>

    @include('accounting::layouts.partials.javascripts')

    <div class="modal fade view_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

    @if (!empty($__additional_views) && is_array($__additional_views))
        @foreach ($__additional_views as $additional_view)
            @includeIf($additional_view)
        @endforeach
    @endif

    <div class="overlay tw-hidden"></div>
</body>

<style>
    html,
    body {
        height: 100%;
        overflow: hidden !important;
    }

    body > .tw-flex {
        height: 100vh;
        overflow: hidden;
    }

    main.tw-flex {
        min-height: 0;
    }

    #scrollable-container {
        min-height: 0;
        position: relative;
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* Keep accounting pages within viewport width like the main app layout */
    body,
    html {
        overflow-x: hidden !important;
        max-width: 100vw;
    }

    .tw-flex {
        max-width: 100vw;
        overflow-x: hidden;
    }

    main {
        max-width: 100%;
        overflow-x: hidden;
    }

    .small-view-side-active {
        display: grid !important;
        z-index: 1000;
        position: absolute;
    }

    .overlay {
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.8);
        position: fixed;
        top: 0;
        left: 0;
        display: none;
        z-index: 20;
    }
</style>

</html>
