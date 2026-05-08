<!-- Install Prompt Modal -->
<div id="pwa-install-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title h5">{{ __('lang_v1.pwa_install_title', ['name' => Session::get('business.name')]) }}</div>
        <button type="button" class="btn-close" id="pwa-modal-close-btn" data-bs-dismiss="modal" aria-label="{{ __('messages.close') }}" style="cursor: pointer;"></button>
      </div>
      <div class="modal-body text-center">
        <p id="pwa-install-description">{{ __('lang_v1.pwa_install_description') }}</p>
        <div id="pwa-ios-instructions" style="display:none; text-align:left">
          <p>{{ __('lang_v1.pwa_ios_install_intro') }}</p>
          <ol style="text-align:left">
            <li>{{ __('lang_v1.pwa_ios_install_step_1') }}</li>
            <li>{{ __('lang_v1.pwa_ios_install_step_2') }}</li>
            <li>{{ __('lang_v1.pwa_ios_install_step_3') }}</li>
          </ol>
        </div>
      </div>
      <div class="modal-footer">
        <button id="pwa-install-btn" type="button" class="btn btn-primary">{{ __('lang_v1.install') }}</button>
        <button id="pwa-dismiss-btn" type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.close') }}</button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
    // Only run in browser
    if (typeof window === 'undefined') return;

    // Server-provided flags: if the server already recorded install/dismiss, don't show the modal.
    // These are rendered by Blade using the authenticated user's fields (if available).
    const serverPwaInstalled = @json(optional(auth()->user())->pwa_installed_at ? true : false);
    const serverPwaDismissed = @json(optional(auth()->user())->pwa_install_dismissed_at ? true : false);
    // Endpoints (use url()/asset() so paths resolve correctly when app is in a subdirectory)
    const PWA_ENDPOINTS = {
        telemetry: "{{ url('pwa/telemetry-public') }}",
        installed: "{{ url('pwa/installed') }}",
        dismissed: "{{ url('pwa/dismissed') }}",
        serviceWorker: "{{ asset('service-worker.js?v=' . $asset_v) }}"
    };

    let deferredPrompt = null;
    const isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent);
    const isInStandaloneMode = ('standalone' in window.navigator) && window.navigator.standalone;

    // Elements
    const installModal = document.getElementById('pwa-install-modal');
    const installBtn = document.getElementById('pwa-install-btn');
    const iosInstructions = document.getElementById('pwa-ios-instructions');

    // Debug helpers: expose some state to window for remote debugging
    try {
        console.debug('PWA debug init', {
            serverPwaInstalled: serverPwaInstalled,
            serverPwaDismissed: serverPwaDismissed,
            isIos: isIos,
            isInStandaloneMode: isInStandaloneMode,
            PWA_ENDPOINTS: PWA_ENDPOINTS
        });
    } catch (e) { /* noop */ }

    window.__bremac_pwa_status = {
        serverPwaInstalled: serverPwaInstalled,
        serverPwaDismissed: serverPwaDismissed,
        isIos: isIos,
        isInStandaloneMode: isInStandaloneMode,
        manifestOk: false,
        swRegistered: false,
        beforeInstallPromptFired: false
    };

    // Check manifest & service worker status for debugging
    async function probeManifestAndSW() {
        try {
            const manifestLink = document.querySelector('link[rel="manifest"]');
            if (manifestLink && manifestLink.href) {
                try {
                    const r = await fetch(manifestLink.href, { method: 'GET', cache: 'no-store' });
                    window.__bremac_pwa_status.manifestOk = r && r.ok;
                    console.debug('PWA manifest fetched', manifestLink.href, r && r.status, r && r.headers && r.headers.get('Content-Type'));
                } catch (e) {
                    window.__bremac_pwa_status.manifestOk = false;
                    console.warn('PWA manifest fetch failed', e);
                }
            } else {
                console.warn('PWA manifest link not found');
            }
        } catch (e) { console.warn('probeManifest error', e); }

        try {
            if ('serviceWorker' in navigator) {
                const reg = await navigator.serviceWorker.getRegistration();
                window.__bremac_pwa_status.swRegistered = !!reg;
                console.debug('PWA service worker registration status', !!reg, reg);
            }
        } catch (e) { console.warn('probeSW error', e); }
    }

    // Expose a helper for manual inspection in remote debug console
    window.__bremac_probe_pwa = probeManifestAndSW;
    // Run an initial probe
    probeManifestAndSW().catch(()=>{});

    // Show modal helper using bootstrap (both v4 & v5 compatible check)
    function showModal() {
        try {
            // Developer/test bypass: if URL has ?pwa_test=1 force-show the modal regardless of saved state
            const urlParams = new URLSearchParams(window.location.search);
            const forceShow = urlParams.get('pwa_test') === '1' || urlParams.get('pwa_test') === 'true';

            // First, if server says user installed or dismissed already, don't show (unless forced)
            if (!forceShow && (serverPwaInstalled || serverPwaDismissed)) {
                return;
            }

            // Then, check local storage flags (unless forced)
            if (!forceShow && (localStorage.getItem('pwa-install-dismissed') === '1' || localStorage.getItem('pwa-installed') === '1')) {
                return;
            }

            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = new bootstrap.Modal(installModal);
                modal.show();
                // Telemetry: modal shown
                try {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    fetch(PWA_ENDPOINTS.telemetry, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ event: 'shown' })
                    });
                } catch (e) { /* noop */ }
            } else if (typeof $ !== 'undefined') {
                $(installModal).modal('show');
                try {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    fetch(PWA_ENDPOINTS.telemetry, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ event: 'shown' })
                    });
                } catch (e) { /* noop */ }
            }
        } catch (e) {
            console.warn('Could not show install modal', e);
        }
    }

    // Expose programmatic modal opener for other scripts
    try {
        window.__bremac_show_install_modal = showModal;
    } catch (e) { /* noop */ }

    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent Chrome 67 and earlier from automatically showing the prompt
        e.preventDefault();
        deferredPrompt = e;
        // show the modal/prompt to the user
        iosInstructions.style.display = 'none';
        showModal();
    });

    // Fallback for browsers that don't support beforeinstallprompt (e.g., iOS)
    window.addEventListener('load', function() {
        // If already installed, don't show
        if (isInStandaloneMode) return;

        // On iOS show manual instructions
        if (isIos) {
            iosInstructions.style.display = 'block';
            installBtn.textContent = @json(__('lang_v1.ok'));
            showModal();
            return;
        }

        // Otherwise, for browsers that support navigator.standalone or no prompt, optionally show a gentle CTA
        // We'll keep it hidden by default unless beforeinstallprompt fired
    });

    installBtn && installBtn.addEventListener('click', async function () {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const choiceResult = await deferredPrompt.userChoice;
            // Optionally handle accepted/ dismissed
            if (choiceResult && choiceResult.outcome === 'accepted') {
                localStorage.setItem('pwa-installed', '1');
                // Persist server-side
                // Telemetry: accepted
                try {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    fetch(PWA_ENDPOINTS.telemetry, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ event: 'accepted' })
                    });
                } catch (e) { /* noop */ }

                try {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    fetch(PWA_ENDPOINTS.installed, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({})
                    });
                } catch (e) { console.warn(e); }
            }
            deferredPrompt = null;
            // hide modal
            try { if (typeof $ !== 'undefined') $(installModal).modal('hide'); } catch(e){}
        } else if (isIos) {
            // iOS: user read instructions, just close modal
            localStorage.setItem('pwa-install-dismissed', '1');
            // Persist server-side
            try {
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                // Telemetry: dismissed
                try {
                    fetch(PWA_ENDPOINTS.telemetry, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ event: 'dismissed' })
                    });
                } catch (e) { /* noop */ }

                fetch(PWA_ENDPOINTS.dismissed, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                });
            } catch (e) { console.warn(e); }
            try { if (typeof $ !== 'undefined') $(installModal).modal('hide'); } catch(e){}
        } else {
            // No prompt available - attempt to register service worker and suggest bookmarking
            localStorage.setItem('pwa-install-dismissed', '1');
            // Persist server-side
            try {
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                // Telemetry: dismissed
                try {
                    fetch(PWA_ENDPOINTS.telemetry, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ event: 'dismissed' })
                    });
                } catch (e) { /* noop */ }

                fetch(PWA_ENDPOINTS.dismissed, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                });
            } catch (e) { console.warn(e); }
            try { if (typeof $ !== 'undefined') $(installModal).modal('hide'); } catch(e){}
        }
    });

    // Service worker is registered globally in the main layout; avoid double registration here.

    // Helper function to handle PWA dismiss action
    function handlePwaDismiss() {
        localStorage.setItem('pwa-install-dismissed', '1');
        try {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            // Telemetry: dismissed
            try {
                fetch(PWA_ENDPOINTS.telemetry, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ event: 'dismissed' })
                });
            } catch (e) { /* noop */ }

            fetch(PWA_ENDPOINTS.dismissed, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            });
        } catch (e) { console.warn(e); }
    }

    // When modal is dismissed via close button (X button), persist a dismissal so we don't annoy users
    const closeBtn = document.getElementById('pwa-modal-close-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            handlePwaDismiss();
            // Let Bootstrap handle the modal closing
            try { 
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const modal = bootstrap.Modal.getInstance(installModal) || new bootstrap.Modal(installModal);
                    modal.hide();
                } else if (typeof $ !== 'undefined') {
                    $(installModal).modal('hide'); 
                }
            } catch(e) {}
        });
    }

    const dismissBtn = document.getElementById('pwa-dismiss-btn');
    if (dismissBtn) {
        dismissBtn.addEventListener('click', function() {
            handlePwaDismiss();
        });
    }

    // If beforeinstallprompt never fires but the user opens modal and closes, set dismissed flag
    if (typeof $ !== 'undefined') {
        $(installModal).on('hidden.bs.modal', function () {
            // If not installed, mark dismissed
            if (!localStorage.getItem('pwa-installed')) {
                localStorage.setItem('pwa-install-dismissed', '1');
            }
        });
    }
})();
</script>
<!-- Install prompt partial rendered for all visitors (authenticated or not) -->
