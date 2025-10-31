<!-- Install Prompt Modal -->
<div id="pwa-install-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Install {{ Session::get('business.name') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <p id="pwa-install-description">Install this app to access it quickly from your device.</p>
        <div id="pwa-ios-instructions" style="display:none; text-align:left">
          <p>To install on iOS (Safari):</p>
          <ol style="text-align:left">
            <li>Tap the Share button ({{ "\u2B07\uFE0F" }} or the box with an arrow).</li>
            <li>Select "Add to Home Screen".</li>
            <li>Tap "Add" in the top-right.</li>
          </ol>
        </div>
      </div>
      <div class="modal-footer">
        <button id="pwa-install-btn" type="button" class="btn btn-primary">Install</button>
        <button id="pwa-dismiss-btn" type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
        serviceWorker: "{{ asset('service-worker.js') }}"
    };

    let deferredPrompt = null;
    const isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent);
    const isInStandaloneMode = ('standalone' in window.navigator) && window.navigator.standalone;

    // Elements
    const installModal = document.getElementById('pwa-install-modal');
    const installBtn = document.getElementById('pwa-install-btn');
    const iosInstructions = document.getElementById('pwa-ios-instructions');

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
            installBtn.textContent = 'Got it';
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

    // Register a simple service worker for offline + PWA install support
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register(PWA_ENDPOINTS.serviceWorker).then(function(reg) {
                // Registered
            }).catch(function(err) {
                console.warn('Service worker registration failed: ', err);
            });
        });
    }

    // When modal is dismissed via close button, persist a dismissal so we don't annoy users
    const dismissBtn = document.getElementById('pwa-dismiss-btn');
    if (dismissBtn) {
        dismissBtn.addEventListener('click', function() {
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
