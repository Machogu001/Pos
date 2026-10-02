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
    // Page opened inside the BreMac360 Android app: the app shows its own app bar and menu.
    $in_app = \App\Utils\MobileAppView::isAppRequest($request);
@endphp

<!DOCTYPE html>
<html class="tw-bg-white tw-scroll-smooth" lang="{{ app()->getLocale() }}"
    dir="{{ in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl')) ? 'rtl' : 'ltr' }}">
<head>
    <!-- Tell the browser to be responsive to screen width -->
    <meta charset="utf-8">
    <!-- Viewport: default to desktop view on phones/tablets unless user opts into mobile view -->
    <meta id="meta-viewport" name="viewport" content="width=1024">
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

            function swallowKnownNoise(event, message) {
                if (!isKnownExtensionMessage(message)) {
                    return;
                }

                try { event.preventDefault(); } catch (e) {}
                try { event.stopImmediatePropagation(); } catch (e) {}
                try { event.stopPropagation(); } catch (e) {}
            }

            window.addEventListener('error', function (event) {
                if (isKnownExtensionMessage(event && event.message)) {
                    swallowKnownNoise(event, event && event.message);
                }
            }, true);

            window.addEventListener('unhandledrejection', function (event) {
                var reason = event ? event.reason : '';
                var message = reason && reason.message ? reason.message : reason;

                if (!isKnownExtensionMessage(message)) {
                    try {
                        message = (reason && reason.stack) ? reason.stack : String(reason || '');
                    } catch (e) {
                        message = String(message || '');
                    }
                }

                swallowKnownNoise(event, message);
            }, true);
        })();
    </script>
    <script>
        (function(){
            try {
                var pref = @if($in_app) 'mobile' @else (localStorage.getItem('preferred_view') || 'desktop') @endif;
                var meta = document.getElementById('meta-viewport');
                function apply(p){
                    if (!meta) return;
                    if (p === 'desktop') {
                        // Render using a wide viewport so site shows desktop layout on small devices
                        meta.setAttribute('content', 'width=1024');
                    } else {
                        // Standard responsive mobile viewport
                        meta.setAttribute('content', 'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=yes');
                    }
                }
                apply(pref);
                // Expose setter so page scripts can toggle and persist preference
                window.setPreferredView = function(p) {
                    localStorage.setItem('preferred_view', p);
                    apply(p);
                    try { location.reload(); } catch (e) { /* noop */ }
                };
            } catch (e) { /* noop */ }
        })();
    </script>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - {{ Session::get('business.name') }}</title>
    @include('layouts.partials.css')

    <!-- PWA manifest and theme -->
    {{-- Prefer a per-business manifest when session business id is present to allow branding per tenant.
        Only use the per-business manifest if the file actually exists under public/manifest/{id}.json.
        This avoids linking to a 404 manifest which breaks PWA installability. --}}
    @php
        $faviconPath = 'favicon-bremac360.ico';
        $faviconVersion = file_exists(public_path($faviconPath)) ? filemtime(public_path($faviconPath)) : time();
        $faviconPngPath = 'pwa-icons/favicon-bremac360.png';
        $faviconPngVersion = file_exists(public_path($faviconPngPath)) ? filemtime(public_path($faviconPngPath)) : $faviconVersion;
        $faviconLightPath = 'pwa-icons/favicon-bremac360-light.png';
        $faviconLightVersion = file_exists(public_path($faviconLightPath)) ? filemtime(public_path($faviconLightPath)) : $faviconVersion;
        $faviconDarkPath = 'pwa-icons/favicon-bremac360-dark.png';
        $faviconDarkVersion = file_exists(public_path($faviconDarkPath)) ? filemtime(public_path($faviconDarkPath)) : $faviconVersion;
        $manifestUrl = url('manifest.json?v=' . time());
        $iconPath = 'pwa-icons/mobile-app-192.png';
        $iconVersion = file_exists(public_path($iconPath)) ? filemtime(public_path($iconPath)) : time();
        if (session('business.id')) {
            $perBusinessPath = public_path('manifest/' . session('business.id') . '.json');
            if (file_exists($perBusinessPath)) {
                $manifestUrl = url('manifest/' . session('business.id') . '.json?v=' . time());
            }
        }
    @endphp
    <link rel="icon" type="image/png" href="{{ asset($faviconPngPath) }}?v={{ $faviconPngVersion }}">
    <link rel="icon" type="image/png" href="{{ asset($faviconLightPath) }}?v={{ $faviconLightVersion }}" media="(prefers-color-scheme: light)">
    <link rel="icon" type="image/png" href="{{ asset($faviconDarkPath) }}?v={{ $faviconDarkVersion }}" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="{{ asset($faviconPath) }}?v={{ $faviconVersion }}" sizes="any">
    <link rel="shortcut icon" href="{{ asset($faviconPath) }}?v={{ $faviconVersion }}" type="image/x-icon">
    <link rel="manifest" href="{{ $manifestUrl }}">
    <meta name="theme-color" content="{{ !empty(session('business.theme_color')) ? session('business.theme_color') : '#2b6cb0' }}">
    <!-- iOS support -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ Session::get('business.name') }}">
    {{-- Use PWA icons with fallback to prevent 404 errors --}}
    @if(file_exists(public_path($iconPath)))
        <link rel="apple-touch-icon" sizes="192x192" href="{{ asset($iconPath) }}?v={{ $iconVersion }}">
    @endif
    <link rel="apple-touch-icon" href="{{ asset($iconPath) }}?v={{ $iconVersion }}">
    

    @include('layouts.partials.extracss')

    @yield('css')

</head>
<body
    class="@if ($in_app) bremac-in-app @endif tw-font-sans tw-antialiased tw-text-gray-900 tw-bg-gray-100 @if ($pos_layout) hold-transition lockscreen @else hold-transition skin-@if (!empty(session('business.theme_color'))){{ session('business.theme_color') }}@else{{ 'blue-light' }} @endif sidebar-mini @endif" >
    <div class="tw-flex tw-h-screen tw-overflow-hidden">
        <script type="text/javascript">
            if (localStorage.getItem("upos_sidebar_collapse") == 'true') {
                var body = document.getElementsByTagName("body")[0];
                body.className += " sidebar-collapse";
            }
        </script>
        @if (!$pos_layout && !$in_app)
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
        {{-- status_span removed: flash handled by the inline showToast script below --}}
        <main class="tw-flex tw-flex-col tw-flex-1 tw-h-full tw-min-w-0 tw-bg-gray-100">

            @if (Module::has('Superadmin') && auth()->check())
                @includeIf('superadmin::layouts.partials.update-banner')
            @endif

            @if (!$pos_layout)
                {{-- In the app the header stays in the page (hidden) so its scripts and modals keep working. --}}
                <div class="bremac-web-header">
                    @include('layouts.partials.header')
                </div>
            @else
                @include('layouts.partials.header-pos')
            @endif
            <!-- empty div for vuejs -->
            <div id="app">
                @yield('vue')
            </div>
            <div class="tw-flex-1 tw-min-h-0 tw-overflow-y-auto" id="scrollable-container">
                @yield('content')
                @if (!$pos_layout)
                    @if (!$in_app)
                        @include('layouts.partials.footer')
                    @endif
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

        <!-- Task/ToDo modal container -->
        <div class="modal fade" id="task_modal" tabindex="-1" role="dialog" aria-hidden="true">
        </div>



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
        @php
            $flashMsg = session('status') ?? session('message') ?? session('success') ?? null;
        @endphp
        <script>
            // Provide a global helper to play success sound consistently
            window.playSuccess = function(){
                try {
                    var el = document.getElementById('success-audio');
                    if (el && typeof el.play === 'function') { el.currentTime = 0; el.play(); }
                } catch(e) {}
            };
            window.playError = function(){
                try {
                    var el = document.getElementById('error-audio');
                    if (el && typeof el.play === 'function') { el.currentTime = 0; el.play(); }
                } catch(e) {}
            };
            window.playWarning = function(){
                try {
                    var el = document.getElementById('warning-audio');
                    if (el && typeof el.play === 'function') { el.currentTime = 0; el.play(); }
                } catch(e) {}
            };
        </script>
        @if($flashMsg)
        <script>
            (function(){
                try {
                    var raw = @json($flashMsg);
                    var msg, type;
                    // status can be an array like {success: true, msg: '...'} or a plain string
                    if (raw && typeof raw === 'object') {
                        msg  = raw.msg  ?? raw.message ?? JSON.stringify(raw);
                        type = (raw.success === true || raw.success === 1) ? 'success' : 'error';
                    } else {
                        msg  = raw;
                        type = 'success';
                    }
                    if (msg) {
                        if (typeof window.showToast === 'function') {
                            window.showToast(type, msg);
                        } else if (window.Swal) {
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 4000,
                                timerProgressBar: true
                            });
                            Toast.fire({ icon: type, title: msg });
                        } else if (window.toastr) {
                            type === 'success' ? toastr.success(msg) : toastr.error(msg);
                        }
                        if (type === 'success') window.playSuccess();
                        else window.playError();
                    }
                } catch(e) {}
            })();
        </script>
        @endif

    {{-- Install prompt modal and registration. Include for all visitors so the client-side
         beforeinstallprompt handler can show the prompt when criteria are met. The
         partial itself checks server-side and local flags before showing the modal. --}}
    @if (!$in_app)
        @include('layouts.partials.install_prompt')
    @endif

    @if ($in_app)
        <script>
            window.__bremacAppMenu = @json(\App\Utils\MobileAppView::menu());
        </script>
        <style>
            body.bremac-in-app .bremac-web-header,
            body.bremac-in-app .scrolltop {
                display: none !important;
            }
            body.bremac-in-app {
                -webkit-tap-highlight-color: transparent;
            }
        </style>
    @endif

        <div class="modal fade view_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

        @if (!empty($__additional_views) && is_array($__additional_views))
            @foreach ($__additional_views as $additional_view)
                @includeIf($additional_view)
            @endforeach
        @endif
        <div>

            <div class="overlay tw-hidden"></div>
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    // Append asset version to bust caches and force browser to fetch latest SW
                    navigator.serviceWorker.register('{{ url("service-worker.js") }}?v={{ $asset_v }}')
                        .then(function() {})
                        .catch(function() {});
                });
            }
        </script>
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
    
    /* Prevent horizontal scrollbar globally */
    body, html {
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




</style>

</html>
