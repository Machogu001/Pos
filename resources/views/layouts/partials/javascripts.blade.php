<script type="text/javascript">
    base_path = "{{ url('/') }}";
    //used for push notification
    APP = {};
    APP.PUSHER_APP_KEY = '{{ config('broadcasting.connections.pusher.key') }}';
    APP.PUSHER_APP_CLUSTER = '{{ config('broadcasting.connections.pusher.options.cluster') }}';
    APP.INVOICE_SCHEME_SEPARATOR = '{{ config('constants.invoice_scheme_separator') }}';
    //variable from app service provider
    APP.PUSHER_ENABLED = '{{ $__is_pusher_enabled }}';
    @auth
    @php
        $user = Auth::user();
    @endphp
    APP.USER_ID = "{{ $user->id }}";
    @else
        APP.USER_ID = '';
    @endauth
</script>

<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js?v=$asset_v"></script>
<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js?v=$asset_v"></script>
<![endif]-->

<script>
    // Ensure CSRF meta exists before vendor scripts run so compiled JS can pick it up.
    try {
        if (!document.head.querySelector('meta[name="csrf-token"]')) {
            var meta = document.createElement('meta');
            meta.name = 'csrf-token';
            meta.content = '{{ csrf_token() }}';
            document.head.appendChild(meta);
        }
    } catch (e) { /* noop */ }
</script>

<script src="{{ asset('js/vendor.js?v=' . $asset_v) }}"></script>

@php
    $selected_lang = session()->get('user.language', config('app.locale'));
    $selected_lang_path = public_path('js/lang/' . $selected_lang . '.js');
    $fallback_lang_path = public_path('js/lang/en.js');
@endphp

@if (file_exists($selected_lang_path))
    <script src="{{ asset('js/lang/' . $selected_lang . '.js?v=' . filemtime($selected_lang_path)) }}">
    </script>
@else
    <script src="{{ asset('js/lang/en.js?v=' . filemtime($fallback_lang_path)) }}"></script>
@endif
@php
    $business_date_format = session('business.date_format', config('constants.default_date_format'));
    $datepicker_date_format = str_replace('d', 'dd', $business_date_format);
    $datepicker_date_format = str_replace('m', 'mm', $datepicker_date_format);
    $datepicker_date_format = str_replace('Y', 'yyyy', $datepicker_date_format);

    $moment_date_format = str_replace('d', 'DD', $business_date_format);
    $moment_date_format = str_replace('m', 'MM', $moment_date_format);
    $moment_date_format = str_replace('Y', 'YYYY', $moment_date_format);

    $moment_time_format = 'HH:mm:ss';

    $common_settings = !empty(session('business.common_settings')) ? session('business.common_settings') : [];

    $default_datatable_page_entries = !empty($common_settings['default_datatable_page_entries'])
        ? $common_settings['default_datatable_page_entries']
        : 25;
@endphp

<script>
    Dropzone.autoDiscover = false;
    moment.tz.setDefault('{{ Session::get('business.time_zone') }}');
    $(document).ready(function() {
        // Header PWA install CTA: show when beforeinstallprompt fires and trigger prompt on click.
        try {
            var headerInstallBtn = document.getElementById('header-pwa-install-btn');

            // Hide if already installed or dismissed
            function headerInstallShouldHide() {
                try {
                    if (localStorage.getItem('pwa-installed') === '1' || localStorage.getItem('pwa-install-dismissed') === '1') return true;
                } catch (e) { /* noop */ }
                // Standalone check
                try {
                    if (('standalone' in window.navigator) && window.navigator.standalone) return true;
                } catch (e) { /* noop */ }
                return false;
            }

            if (headerInstallBtn) {
                if (headerInstallShouldHide()) {
                    headerInstallBtn.style.display = 'none';
                }

                // Listen for global beforeinstallprompt (may be fired by other partials too)
                window.addEventListener('beforeinstallprompt', function (e) {
                    try {
                        // Keep the event for user-triggered install flow without suppressing native behavior.
                        window.__bremac_deferredPrompt = e;
                        window.__bremac_pwa_status = window.__bremac_pwa_status || {};
                        window.__bremac_pwa_status.beforeInstallPromptFired = true;
                        // If user already installed/dismissed, don't show
                        if (headerInstallShouldHide()) return;
                        headerInstallBtn.style.display = '';
                        headerInstallBtn.removeAttribute('aria-hidden');
                    } catch (err) { console.warn('header beforeinstallprompt handler', err); }
                });

                // If appinstalled event fires, hide the button
                window.addEventListener('appinstalled', function () {
                    try { localStorage.setItem('pwa-installed', '1'); } catch (e) {}
                    headerInstallBtn.style.display = 'none';
                });

                headerInstallBtn.addEventListener('click', async function () {
                    try {
                        var dp = window.__bremac_deferredPrompt;
                        if (dp) {
                            dp.prompt();
                            var choice = await dp.userChoice;
                            if (choice && choice.outcome === 'accepted') {
                                try { localStorage.setItem('pwa-installed', '1'); } catch (e) {}
                            } else {
                                try { localStorage.setItem('pwa-install-dismissed', '1'); } catch (e) {}
                            }
                            window.__bremac_deferredPrompt = null;
                            headerInstallBtn.style.display = 'none';
                        } else {
                            // Fallback: open the install modal if present
                            var installModalBtn = document.getElementById('pwa-install-btn');
                            if (installModalBtn) {
                                try { installModalBtn.click(); } catch (e) { console.warn('could not open install modal', e); }
                            }
                        }
                    } catch (e) { console.warn('header install click error', e); }
                });
                // If beforeinstallprompt never fired, attempt a gentle fallback after a short delay:
                // if manifest + SW look good, show CTA so user can open the install modal.
                setTimeout(async function () {
                    try {
                        if (window.__bremac_deferredPrompt) return; // native prompt available
                        // Ensure we have probe data; if not, run a quick probe
                        if (!window.__bremac_pwa_status) {
                            // If install_prompt partial is present it exposes __bremac_probe_pwa
                            if (typeof window.__bremac_probe_pwa === 'function') {
                                await window.__bremac_probe_pwa();
                            }
                        }
                        var status = window.__bremac_pwa_status || {};
                        var canShow = (status.manifestOk && status.swRegistered) && !headerInstallShouldHide();
                        if (canShow) {
                            // Show header CTA
                            headerInstallBtn.style.display = '';
                            headerInstallBtn.removeAttribute('aria-hidden');
                            // Aggressive fallback: open our install modal programmatically so user sees install instructions
                            try {
                                if (typeof window.__bremac_show_install_modal === 'function') {
                                    window.__bremac_show_install_modal();
                                }
                            } catch (e) { console.warn('could not show install modal programmatically', e); }
                        }
                    } catch (e) { console.warn('header CTA fallback error', e); }
                }, 1800);
            }
        } catch (e) { console.warn('header install CTA init error', e); }
        // Initialize view toggle button label and behavior
        try {
            var btn = document.getElementById('view-toggle-btn');
            var lbl = document.getElementById('view-toggle-label');

            function getPreferred() {
                return localStorage.getItem('preferred_view') || 'desktop';
            }

            var desktopViewLabel = @json(__('lang_v1.desktop_view'));
            var mobileViewLabel = @json(__('lang_v1.mobile_view'));

            function updateLabel(p) {
                if (!lbl) return;
                lbl.textContent = (p === 'desktop') ? desktopViewLabel : mobileViewLabel;
            }

            // Treat phones as <= 767px. Devices with width >= 1024 are considered desktop by layout, but
            // we only show the toggle on phones (<=767px) and hide it when the user preference is 'desktop'.
            function isPhone() {
                try {
                    return window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
                } catch (e) {
                    return (window.innerWidth || document.documentElement.clientWidth) <= 767;
                }
            }

            function updateViewToggleVisibility() {
                if (!btn) return;
                var pref = getPreferred();
                // Show only on phones, and only when pref !== 'desktop'
                if (!isPhone() || pref === 'desktop') {
                    btn.style.display = 'none';
                } else {
                    btn.style.display = '';
                }
            }

            // Debounced resize handler
            var _resizeTimer;
            window.addEventListener('resize', function () {
                clearTimeout(_resizeTimer);
                _resizeTimer = setTimeout(function () {
                    updateViewToggleVisibility();
                }, 150);
            });

            if (btn) {
                var pref = getPreferred();
                updateLabel(pref);
                updateViewToggleVisibility();

                btn.addEventListener('click', function () {
                    var current = getPreferred();
                    var next = current === 'desktop' ? 'mobile' : 'desktop';

                    // Update label immediately
                    updateLabel(next);

                    // Update visibility before navigation so users see immediate effect
                    try {
                        localStorage.setItem('preferred_view', next);
                    } catch (e) {
                        console.warn('Could not set preferred_view in localStorage', e);
                    }

                    updateViewToggleVisibility();

                    // Use the global setter if available, otherwise reload after small delay
                    if (typeof window.setPreferredView === 'function') {
                        try {
                            window.setPreferredView(next);
                        } catch (e) {
                            // Fallback to reload
                            setTimeout(function () { location.reload(); }, 120);
                        }
                    } else {
                        setTimeout(function () { location.reload(); }, 120);
                    }
                });
            }
        } catch (e) { console.warn('view toggle init error', e); }
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        @if (config('app.debug') == false)
            $.fn.dataTable.ext.errMode = 'throw';
        @endif
    });

    var financial_year = {
        start: moment('{{ Session::get('financial_year.start') }}'),
        end: moment('{{ Session::get('financial_year.end') }}'),
    }
    @if (file_exists(public_path('AdminLTE/plugins/select2/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
        //Default setting for select2
        $.fn.select2.defaults.set("language", "{{ session()->get('user.language', config('app.locale')) }}");
    @endif

    var datepicker_date_format = "{{ $datepicker_date_format }}";
    var moment_date_format = "{{ $moment_date_format }}";
    var moment_time_format = "{{ $moment_time_format }}";

    var app_locale = "{{ session()->get('user.language', config('app.locale')) }}";

    var non_utf8_languages = [
        @foreach (config('constants.non_utf8_languages') as $const)
            "{{ $const }}",
        @endforeach
    ];

    var __default_datatable_page_entries = "{{ $default_datatable_page_entries }}";

    var __new_notification_count_interval = "{{ config('constants.new_notification_count_interval', 60) }}000";
</script>

@if (file_exists($selected_lang_path))
    <script src="{{ asset('js/lang/' . $selected_lang . '.js?v=' . filemtime($selected_lang_path)) }}">
    </script>
@else
    <script src="{{ asset('js/lang/en.js?v=' . filemtime($fallback_lang_path)) }}"></script>
@endif

<script src="{{ asset('js/functions.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/common.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/app.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/help-tour.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/documents_and_note.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/ajax-forms.js?v=' . $asset_v) }}"></script>

<script>
    // Global toast helper using SweetAlert2 if available; falls back to native toast UI.
    window.showToast = function(type, title) {
        try {
            if (typeof Swal !== 'undefined') {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end', // top-right corner
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'swal2-toast-custom'
                    },
                });

                Toast.fire({
                    icon: type,
                    title: title,
                    background: type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#f59e0b'),
                    color: '#ffffff',
                    iconColor: '#ffffff'
                });
                return;
            }
        } catch (e) {
            console.error('showToast error:', e);
        }

        // Fallback native toast
        (function(){
            const existing = document.querySelector('.native-toast');
            if (existing) existing.remove();
            const toast = document.createElement('div');
            toast.className = `native-toast native-toast-${type}`;
            toast.style.position = 'fixed';
            toast.style.top = '1rem';
            toast.style.right = '1rem';
            toast.style.zIndex = 9999;
            toast.style.padding = '0.75rem 1rem';
            toast.style.borderRadius = '0.5rem';
            toast.style.boxShadow = '0 6px 18px rgba(0,0,0,0.12)';
            toast.style.background = type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#f59e0b');
            toast.style.color = '#fff';
            toast.innerText = title;
            document.body.appendChild(toast);
            setTimeout(() => { toast.remove(); }, 4000);
        })();
    };
</script>

<!-- TODO -->
@if (file_exists(public_path('AdminLTE/plugins/select2/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
    <script
        src="{{ asset('AdminLTE/plugins/select2/lang/' . session()->get('user.language', config('app.locale')) . '.js?v=' . $asset_v) }}">
    </script>
@endif
@php
    $validation_lang_file = 'messages_' . session()->get('user.language', config('app.locale')) . '.js';
@endphp
@if (file_exists(public_path() . '/js/jquery-validation-1.16.0/src/localization/' . $validation_lang_file))
    <script src="{{ asset('js/jquery-validation-1.16.0/src/localization/' . $validation_lang_file . '?v=' . $asset_v) }}">
    </script>
@endif

@if (!empty($__system_settings['additional_js']))
    {!! $__system_settings['additional_js'] !!}
@endif
@yield('javascript')

{{-- Render any pushed inline scripts (used by many partials via @push('scripts')) --}}
@stack('scripts')

@if (Module::has('Essentials'))
    @includeIf('essentials::layouts.partials.footer_part')
@endif

<script type="text/javascript">
    $(document).ready(function() {
        var locale = "{{ session()->get('user.language', config('app.locale')) }}";
        var isRTL =
            @if (in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl')))
                true;
            @else
                false;
            @endif

        $('#calendar').fullCalendar('option', {
            locale: locale,
            isRTL: isRTL
        });
        // side bar toggle  
        $(".drop_down").click(function(event) {
            event.preventDefault();
            var $chiled = $(this).next(".chiled");
            var svgElement = $(this).find(".svg");
            $(".chiled").not($chiled).slideUp();
            $chiled.slideToggle(function() {
                $(".svg").each(function() {
                    var $currentSvgElement = $(this);
                    if ($currentSvgElement.closest(".drop_down").next(".chiled").is(
                            ":visible")) {
                        // If the corresponding menu is visible, set the arrow pointing upwards
                        $currentSvgElement.html(
                            '<path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M6 9l6 6l6 -6" />'
                        );
                    } else {
                        // Otherwise, set the arrow pointing downwards
                        $currentSvgElement.html(
                            '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" />'
                        );
                    }
                });
            });
        });

        $('.small-view-button').on('click', function() {
            $('.side-bar').addClass('small-view-side-active');
            $('.overlay').fadeIn('slow');
        });

        $('.overlay').on('click', function() {
            $('.overlay').fadeOut('slow');
            $('.side-bar').removeClass('small-view-side-active');
        });

        $(window).on('resize', function() {
            if ($(window).width() >= 992) {
                $('.overlay').fadeOut('slow');
                $('.side-bar').removeClass('small-view-side-active');
            }

            if($('.side-bar').hasClass('small-view-side-active')){
                $('.overlay').fadeIn('slow');
            }
        });

        $(document).on('click', '.sidebar-child-link', function(event) {
            event.stopPropagation();

            var href = $(this).attr('href');
            if (href && href !== '#' && href !== 'javascript:void(0)') {
                window.location.href = href;
            }
        });

        function initializeHelpOverlays(context) {
            var $scope = context ? $(context) : $(document);
            $scope.find('[data-toggle="popover"], [data-bs-toggle="popover"]').popover();
            $scope.find('[data-toggle="tooltip"], [data-bs-toggle="tooltip"]').tooltip();
        }

        initializeHelpOverlays(document);

        $(document).on('click', function (e) {
            $('[data-toggle="popover"], [data-bs-toggle="popover"]').each(function () {
                // Hide open popovers when clicking outside of trigger or popover body.
                if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
                    $(this).popover('hide');
                }
            });
        });

        $('.side-bar-collapse').click(function() {
            $('.side-bar').toggle('slow');
        });

        $('.dt-buttons.btn-group').find('a.btn').removeClass('btn-default');
        $('.dt-buttons.btn-group').find('a.btn').removeClass('btn');
        
        // $('.date_range').on('show.daterangepicker', function (ev, picker) {
        //     $(picker.container).insertAfter($(this));
        // });
   
    });
</script>


