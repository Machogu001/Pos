@inject('request', 'Illuminate\Http\Request')

@php
    // Detect POS layout by route name where possible. This is more reliable than
    // checking URL segments (which can vary across deployments with /public in the path).
    // Treat routes named 'pos.*' as POS pages and hide the admin sidebar on them.
    $pos_layout = request()->routeIs('pos.*');
    // Fallback to legacy segment checks for routes that may not have route names set
    if (! $pos_layout) {
        $pos_layout = ($request->segment(1) == 'pos' &&
            ($request->segment(2) == 'create' || $request->segment(3) == 'edit' || $request->segment(2) == 'payment'));
    }
@endphp

@php
    $whitelist = ['127.0.0.1', '::1'];
@endphp

<!DOCTYPE html>
<html class="tw-bg-white tw-scroll-smooth" lang="{{ app()->getLocale() }}"
    dir="{{ in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl')) ? 'rtl' : 'ltr' }}">
<head>
    <!-- Tell the browser to be responsive to screen width -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"
        name="viewport">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - {{ Session::get('business.name') }}</title>
    @include('layouts.partials.css')

    <!-- PWA manifest and theme -->
    {{-- Prefer a per-business manifest when session business id is present to allow branding per tenant. 
        Fallback to /manifest.json when no business context is available. --}}
    <link rel="manifest" href="{{ session('business.id') ? '/manifest/' . session('business.id') . '.json' : '/manifest.json' }}">
    <meta name="theme-color" content="{{ !empty(session('business.theme_color')) ? session('business.theme_color') : '#2b6cb0' }}">
    <!-- iOS support -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ Session::get('business.name') }}">
    {{-- Use an explicit 192px icon for iOS/apple-touch where possible; fallback to configured favicon. 
        If you'd like local PNG icons, add them to public/icons/icon-192.png and icon-512.png. --}}
    <link rel="apple-touch-icon" sizes="192x192" href="/icons/icon-192.png">
    <link rel="apple-touch-icon" href="/{{ config('app.favicon', 'favicon.ico') }}">
    

    @include('layouts.partials.extracss')

    @yield('css')

</head>
<body
    class="tw-font-sans tw-antialiased tw-text-gray-900 tw-bg-gray-100 @if ($pos_layout) hold-transition lockscreen @else hold-transition skin-@if (!empty(session('business.theme_color'))){{ session('business.theme_color') }}@else{{ 'blue-light' }} @endif sidebar-mini @endif" >
    <div class="tw-flex">
        <script type="text/javascript">
            if (localStorage.getItem("upos_sidebar_collapse") == 'true') {
                var body = document.getElementsByTagName("body")[0];
                body.className += " sidebar-collapse";
            }
        </script>
        @if (!$pos_layout)
            @include('layouts.partials.sidebar')
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
        <input type="hidden" id="__precision" value="{{ session('business.currency_precision', 2) }}">
        <input type="hidden" id="__quantity_precision" value="{{ session('business.quantity_precision', 2) }}">
        <!-- End of currency related field-->
        @can('view_export_buttons')
            <input type="hidden" id="view_export_buttons">
        @endcan
        @if (isMobile())
            <input type="hidden" id="__is_mobile">
        @endif
        @if (session('status'))
            <input type="hidden" id="status_span" data-status="{{ session('status.success') }}"
                data-msg="{{ session('status.msg') }}">
        @endif
        <main class="tw-flex tw-flex-col tw-flex-1 tw-h-full tw-min-w-0 tw-bg-gray-100">

            @if (!$pos_layout)
                @include('layouts.partials.header')
            @else
                @include('layouts.partials.header-pos')
            @endif
            <!-- empty div for vuejs -->
            <div id="app">
                @yield('vue')
            </div>
            <div class="tw-flex-1 tw-overflow-y-auto tw-h-screen" id="scrollable-container">
                @yield('content')
                @if (!$pos_layout)
                
                    @include('layouts.partials.footer')
                @else
                    @include('layouts.partials.footer_pos')
                @endif
            </div>
            <div class='scrolltop no-print'>
                <div class='scroll icon'><i class="fas fa-angle-up"></i></div>
            </div>

            @if (config('constants.iraqi_selling_price_adjustment'))
                <input type="hidden" id="iraqi_selling_price_adjustment">
            @endif

            <!-- This will be printed -->
            <section class="invoice print_section" id="receipt_section">
            </section>
        </main>

        @include('home.todays_profit_modal')
        <!-- /.content-wrapper -->



        <audio id="success-audio">
            <source src="{{ asset('/audio/success.ogg?v=' . $asset_v) }}" type="audio/ogg">
            <source src="{{ asset('/audio/success.mp3?v=' . $asset_v) }}" type="audio/mpeg">
        </audio>
        <audio id="error-audio">
            <source src="{{ asset('/audio/error.ogg?v=' . $asset_v) }}" type="audio/ogg">
            <source src="{{ asset('/audio/error.mp3?v=' . $asset_v) }}" type="audio/mpeg">
        </audio>
        <audio id="warning-audio">
            <source src="{{ asset('/audio/warning.ogg?v=' . $asset_v) }}" type="audio/ogg">
            <source src="{{ asset('/audio/warning.mp3?v=' . $asset_v) }}" type="audio/mpeg">
        </audio>

        @if (!empty($__additional_html))
            {!! $__additional_html !!}
        @endif

        @include('layouts.partials.javascripts')

    {{-- Install prompt modal and registration (only included for authenticated users).
         Avoid including the partial when server already knows the user installed or dismissed. --}}
    @auth
        @if (! (optional(auth()->user())->pwa_installed_at || optional(auth()->user())->pwa_install_dismissed_at))
            @include('layouts.partials.install_prompt')
        @endif
    @endauth

        <div class="modal fade view_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

        @if (!empty($__additional_views) && is_array($__additional_views))
            @foreach ($__additional_views as $additional_view)
                @includeIf($additional_view)
            @endforeach
        @endif
        <div>

            <div class="overlay tw-hidden"></div>
</body>
<style>
    @media print {
  #scrollable-container {
    overflow: visible !important;
    height: auto !important;
  }
}
</style>
<style>
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

    .tw-dw-btn.tw-dw-btn-xs.tw-dw-btn-outline {
        width: max-content;
        margin: 2px;
    }

    #scrollable-container{
        position:relative;
    }
    



</style>

</html>
