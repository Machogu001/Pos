@php
        $pwaModalIconPath = 'pwa-icons/mobile-app-192.png';
        $pwaModalIconVersion = file_exists(public_path($pwaModalIconPath)) ? filemtime(public_path($pwaModalIconPath)) : time();
@endphp

<!-- Install Prompt Modal -->
<div id="pwa-install-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 320px; width: calc(100% - 24px); margin: 1.5rem auto;">
        <div class="modal-content" style="border-radius: 18px; overflow: hidden; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18); border: 0;">
                        <div class="modal-header" style="padding: 14px 16px 8px; border-bottom: 0; align-items: center;">
                <div class="modal-title h5">{{ __('lang_v1.pwa_install_title', ['name' => Session::get('business.name')]) }}</div>
                <button type="button" class="close" id="pwa-modal-close-btn" data-dismiss="modal" aria-label="{{ __('messages.close') }}" style="cursor: pointer; font-size: 24px; line-height: 1; opacity: 0.7;">
                        <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center" style="padding: 4px 18px 12px;">
                <img
                    src="{{ asset($pwaModalIconPath) }}?v={{ $pwaModalIconVersion }}"
                    alt="{{ config('app.name', 'POS') }}"
                                        width="60"
                                        height="60"
                                        style="display:block; margin:0 auto 10px; border-radius:14px; box-shadow: 0 10px 24px rgba(25, 118, 210, 0.18);"
                >
                <p id="pwa-install-description" style="margin: 0 0 10px; font-size: 14px; line-height: 1.45; color: #4b5563;">{{ __('lang_v1.pwa_install_description') }}</p>
        <div id="pwa-ios-instructions" style="display:none; text-align:left">
          <p>{{ __('lang_v1.pwa_ios_install_intro') }}</p>
          <ol style="text-align:left">
            <li>{{ __('lang_v1.pwa_ios_install_step_1') }}</li>
            <li>{{ __('lang_v1.pwa_ios_install_step_2') }}</li>
            <li>{{ __('lang_v1.pwa_ios_install_step_3') }}</li>
          </ol>
        </div>
      </div>
            <div class="modal-footer" style="padding: 0 18px 18px; border-top: 0; display: flex; gap: 10px; justify-content: center;">
                <button id="pwa-install-btn" type="button" class="btn btn-primary" style="min-width: 110px; border-radius: 10px; font-weight: 600;">{{ __('lang_v1.install') }}</button>
                <button id="pwa-dismiss-btn" type="button" class="btn btn-secondary" data-dismiss="modal" style="min-width: 96px; border-radius: 10px;">{{ __('messages.close') }}</button>
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
    const serverPwaDismissedAt = @json(optional(auth()->user())->pwa_install_dismissed_at ? optional(auth()->user())->pwa_install_dismissed_at->toIso8601String() : null);
    const PWA_DISMISS_DAYS = {{ max((int) config('constants.pwa_install_dismiss_days', 15), 1) }};
    const PWA_DISMISS_COOLDOWN_MS = PWA_DISMISS_DAYS * 24 * 60 * 60 * 1000;
    // Endpoints (use url()/asset() so paths resolve correctly when app is in a subdirectory)
    const PWA_ENDPOINTS = {
        telemetry: "{{ url('pwa/telemetry-public') }}",
        installed: "{{ url('pwa/installed') }}",
        dismissed: "{{ url('pwa/dismissed') }}",
        serviceWorker: "{{ asset('service-worker.js?v=' . $asset_v) }}"
    };

    function parseStoredDismissedAt(rawValue) {
        if (!rawValue) return null;
        if (rawValue === '1') {
            try { localStorage.removeItem('pwa-install-dismissed'); } catch (e) { /* noop */ }
            return null;
        }

        const numericValue = Number(rawValue);
        if (!Number.isNaN(numericValue) && numericValue > 0) {
            return numericValue;
        }

        const parsed = Date.parse(rawValue);
        return Number.isNaN(parsed) ? null : parsed;
    }

    function getLocalDismissedAt() {
        try {
            return parseStoredDismissedAt(localStorage.getItem('pwa-install-dismissed'));
        } catch (e) {
            return null;
        }
    }

    function isDismissedWithinCooldown(timestamp) {
        return !!timestamp && (Date.now() - timestamp) < PWA_DISMISS_COOLDOWN_MS;
    }

    function setDismissedCooldown() {
        const timestamp = Date.now();
        try {
            localStorage.setItem('pwa-install-dismissed', String(timestamp));
        } catch (e) { /* noop */ }
        return timestamp;
    }

    function clearDismissedCooldown() {
        try {
            localStorage.removeItem('pwa-install-dismissed');
        } catch (e) { /* noop */ }
    }

    async function markInstalledOnServer() {
        try {
            const tokenEl = document.querySelector('meta[name="csrf-token"]');
            const token = tokenEl ? tokenEl.getAttribute('content') : null;
            if (!token) return;

            await fetch(PWA_ENDPOINTS.installed, {
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

    const serverDismissedAtMs = serverPwaDismissedAt ? Date.parse(serverPwaDismissedAt) : null;

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
            serverPwaDismissedAt: serverPwaDismissedAt,
            isIos: isIos,
            isInStandaloneMode: isInStandaloneMode,
            PWA_ENDPOINTS: PWA_ENDPOINTS
        });
    } catch (e) { /* noop */ }

    window.__bremac_pwa_status = {
        serverPwaInstalled: serverPwaInstalled,
        serverPwaDismissedAt: serverPwaDismissedAt,
        dismissDays: PWA_DISMISS_DAYS,
        isIos: isIos,
        isInStandaloneMode: isInStandaloneMode,
        manifestOk: false,
        swRegistered: false,
        beforeInstallPromptFired: false
    };

    try {
        window.__bremac_mark_pwa_installed = async function () {
            try { localStorage.setItem('pwa-installed', '1'); } catch (e) { /* noop */ }
            clearDismissedCooldown();
            window.__bremac_pwa_status = window.__bremac_pwa_status || {};
            window.__bremac_pwa_status.serverPwaInstalled = true;
            await markInstalledOnServer();
        };
    } catch (e) { /* noop */ }

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
            const locallyDismissedAt = getLocalDismissedAt();

            // First, if server says user installed or the dismissal is still cooling down, don't show (unless forced)
            if (!forceShow && (serverPwaInstalled || isDismissedWithinCooldown(serverDismissedAtMs))) {
                return;
            }

            // Then, check local storage flags (unless forced)
            if (!forceShow && (isDismissedWithinCooldown(locallyDismissedAt) || localStorage.getItem('pwa-installed') === '1')) {
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

    function hideModal() {
        try {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modalInstance = bootstrap.Modal.getInstance(installModal) || new bootstrap.Modal(installModal);
                modalInstance.hide();
            } else if (typeof $ !== 'undefined') {
                $(installModal).modal('hide');
            } else if (installModal) {
                installModal.style.display = 'none';
                installModal.classList.remove('in', 'show');
                installModal.setAttribute('aria-hidden', 'true');
            }
        } catch (e) {
            console.warn('Could not hide install modal', e);
        }
    }

    // Expose programmatic modal opener for other scripts
    try {
        window.__bremac_show_install_modal = showModal;
    } catch (e) { /* noop */ }

    window.addEventListener('beforeinstallprompt', (e) => {
        // Defer the native prompt so it can be triggered from our install button.
        e.preventDefault();
        deferredPrompt = e;
        window.__bremac_deferredPrompt = e;
        window.__bremac_pwa_status.beforeInstallPromptFired = true;
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
        deferredPrompt = deferredPrompt || window.__bremac_deferredPrompt || null;
        if (deferredPrompt) {
            hideModal();
            await new Promise((resolve) => setTimeout(resolve, 150));
            deferredPrompt.prompt();
            const choiceResult = await deferredPrompt.userChoice;
            // Optionally handle accepted/ dismissed
            if (choiceResult && choiceResult.outcome === 'accepted') {
                await window.__bremac_mark_pwa_installed();
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
            }
            deferredPrompt = null;
            window.__bremac_deferredPrompt = null;
        } else if (isIos) {
            // iOS: user read instructions, just close modal
            setDismissedCooldown();
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
            hideModal();
        } else {
            try {
                if (window.toastr) {
                    toastr.warning('Install prompt is not available yet in this browser. Refresh the page and use Chrome or Edge, or install from the browser menu.');
                } else if (window.Swal) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Install not available',
                        text: 'This browser has not exposed an install prompt for this page yet. Refresh the page and try again, or use the browser menu to install the app.'
                    });
                } else {
                    alert('Install prompt is not available yet in this browser. Refresh the page and try again, or use the browser menu to install the app.');
                }
            } catch (e) {
                console.warn(e);
            }
        }
    });

    // Service worker is registered globally in the main layout; avoid double registration here.

    // Helper function to handle PWA dismiss action
    function handlePwaDismiss() {
        setDismissedCooldown();
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
            hideModal();
        });
    }

    const dismissBtn = document.getElementById('pwa-dismiss-btn');
    if (dismissBtn) {
        dismissBtn.addEventListener('click', function() {
            handlePwaDismiss();
            hideModal();
        });
    }

    // If beforeinstallprompt never fires but the user opens modal and closes, set dismissed flag
    if (typeof $ !== 'undefined') {
        $(installModal).on('hidden.bs.modal', function () {
            // If not installed, mark dismissed
            if (!localStorage.getItem('pwa-installed')) {
                setDismissedCooldown();
            }
        });
    }
})();
</script>
<!-- Install prompt partial rendered for all visitors (authenticated or not) -->
