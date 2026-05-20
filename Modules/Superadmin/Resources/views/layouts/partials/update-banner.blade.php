@php
    use App\System;
    use Composer\Semver\Comparator;

    $codeVersion      = config('author.app_version', '0');
    $installedVersion = System::getProperty('app_version') ?? '';

    // Only trust remote_available_version when an update_check_url is configured;
    // otherwise the stored value may be stale from a prior environment.
    $checkUrl      = config('author.update_check_url', '');
    $remoteVersion = ($checkUrl !== '')
        ? (System::getProperty('remote_available_version') ?? '')
        : '';

    $localPending  = $installedVersion === ''
        || Comparator::greaterThan($codeVersion, $installedVersion);

    $remotePending = $remoteVersion !== ''
        && Comparator::greaterThan($remoteVersion, $installedVersion);

    $updatePending  = $localPending || $remotePending;
    $displayVersion = ($remotePending && ! $localPending) ? $remoteVersion : $codeVersion;

    // Respect user dismiss (24-hour snooze stored in session)
    $dismissedUntil = session('update_dismissed_until', 0);
    $isSnoozed      = $dismissedUntil > now()->timestamp;

    $isSuperadmin = auth()->check() && (auth()->user()->role === 'admin' || auth()->user()->can('superadmin'));

    // Client-server: show "Pull & Deploy" when this instance points at a central server.
    $hasUpdateServer = $isSuperadmin && ! empty(env('UPDATE_SERVER_URL'));

    // Central-server: show client registry when a download token is configured.
    $hasClientRegistry = $isSuperadmin && ! empty(env('UPDATE_DOWNLOAD_TOKEN'));
@endphp

{{-- ─── Apply Update Modal (always rendered for superadmins) ───────────── --}}
@if($isSuperadmin)
<div id="update-modal"
     style="display:none;position:fixed;inset:0;z-index:10000;align-items:center;justify-content:center;background:rgba(0,0,0,0.6);backdrop-filter:blur(2px);">
    <div class="tw-bg-white tw-rounded-xl tw-shadow-2xl tw-w-full tw-mx-4 tw-flex tw-flex-col"
         style="max-height:92vh;height:92vh;width:100%;max-width:60rem;margin-left:1rem;margin-right:1rem;display:flex;flex-direction:column;background:#fff;border-radius:0.75rem;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
        {{-- Modal header --}}
        <div class="tw-flex tw-items-center tw-justify-between tw-px-6 tw-py-4 tw-border-b tw-border-gray-200">
            <div class="tw-flex tw-items-center tw-gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5 tw-text-amber-500" viewBox="0 0 24 24"
                     stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/>
                    <path d="M7 11l5 5l5 -5"/>
                    <path d="M12 4l0 12"/>
                </svg>
                <h3 id="update-modal-title" class="tw-text-base tw-font-semibold tw-text-gray-900">
                    System Update — v{{ $codeVersion }}
                </h3>
            </div>
            <button id="close-update-modal" type="button"
                    class="tw-text-gray-400 hover:tw-text-gray-600 tw-transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5" viewBox="0 0 24 24"
                     stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                     <path d="M18 6l-12 12"/><path d="M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Modal body --}}
        <div style="padding:1.25rem 1.5rem;display:grid;grid-template-columns:1fr 1fr;grid-template-rows:auto 1fr;gap:1rem;flex:1 1 0;min-height:0;overflow-y:auto;">

            {{-- Result from "Check for Updates" button — spans both columns --}}
            <div id="update-check-result" style="display:none;border-radius:0.5rem;padding:0.75rem 1rem;font-size:0.875rem;font-weight:500;grid-column:1/-1;"></div>

            {{-- ── Column 1: Build & Push ──────────────────────────────── --}}
            <div style="border:1px solid #e5e7eb;border-radius:0.625rem;padding:1rem;display:flex;flex-direction:column;gap:0.75rem;">
                <div>
                    <p style="font-size:0.875rem;font-weight:600;color:#111827;margin:0 0 0.375rem 0;">Build &amp; Push Update</p>
                    <p style="font-size:0.8125rem;color:#4b5563;margin:0 0 0.75rem 0;">
                        Package the current codebase <strong>(v{{ $codeVersion }})</strong> into a distributable zip and push it to all registered client servers.
                    </p>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                        <button type="button" id="build-package-btn"
                                style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.4rem 0.875rem;font-size:0.8125rem;font-weight:600;color:#fff;background:#16a34a;border:none;border-radius:0.5rem;cursor:pointer;white-space:nowrap;"
                                title="Package current codebase and push to all clients">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:0.875rem;height:0.875rem;flex-shrink:0;" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l8 4.5v9l-8 4.5l-8-4.5v-9l8-4.5"/><path d="M12 12l8-4.5"/><path d="M12 12v9"/><path d="M12 12l-8-4.5"/></svg>
                            <span id="build-pkg-text">Build &amp; Push All Clients</span>
                            <span id="build-pkg-spinner" style="display:none;">
                                <svg class="tw-animate-spin" style="width:0.875rem;height:0.875rem;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle style="opacity:0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path style="opacity:0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"></path>
                                </svg>
                            </span>
                        </button>
                        @if($hasClientRegistry)
                        <button type="button" id="push-all-btn"
                                style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.4rem 0.875rem;font-size:0.8125rem;font-weight:600;color:#fff;background:#6366f1;border:none;border-radius:0.5rem;cursor:pointer;white-space:nowrap;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:0.875rem;height:0.875rem;flex-shrink:0;" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/><path d="M7 11l5-5l5 5"/><path d="M12 4v12"/></svg>
                            Push All Clients
                        </button>
                        @endif
                    </div>
                </div>

                @if($hasClientRegistry)
                {{-- Registered Clients panel --}}
                <div id="update-clients-wrap" style="flex:1;padding-top:0.75rem;border-top:1px solid #f3f4f6;overflow-y:auto;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                        <span style="font-size:0.6875rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;">Registered Client Servers</span>
                        <button type="button" id="add-client-btn"
                                style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.75rem;font-weight:500;color:#b45309;background:none;border:none;cursor:pointer;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:0.875rem;height:0.875rem;" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                            Add Client
                        </button>
                    </div>
                    <div id="add-client-form" style="display:none;gap:0.5rem;margin-bottom:0.75rem;flex-wrap:wrap;">
                        <input id="new-client-name" type="text" placeholder="Name"
                               style="flex:1;min-width:5rem;font-size:0.75rem;border:1px solid #d1d5db;border-radius:0.25rem;padding:0.25rem 0.5rem;">
                        <input id="new-client-url" type="url" placeholder="https://client.example.com"
                               style="flex:2;min-width:10rem;font-size:0.75rem;border:1px solid #d1d5db;border-radius:0.25rem;padding:0.25rem 0.5rem;">
                        <button type="button" id="save-client-btn"
                                style="font-size:0.75rem;font-weight:500;background:#f59e0b;color:#fff;border:none;border-radius:0.25rem;padding:0.25rem 0.75rem;cursor:pointer;">Save</button>
                        <button type="button" id="cancel-add-client-btn"
                                style="font-size:0.75rem;color:#6b7280;background:none;border:none;cursor:pointer;">Cancel</button>
                    </div>
                    <div id="client-secret-box"
                         style="display:none;border-radius:0.375rem;background:#fffbeb;border:1px solid #fcd34d;padding:0.75rem;margin-bottom:0.75rem;font-size:0.75rem;">
                        <p style="font-weight:600;color:#92400e;margin:0 0 0.25rem 0;">Set this on the client server's <code style="font-family:monospace;">.env</code> — it will not be shown again:</p>
                        <code id="client-secret-value"
                              style="display:block;background:#fff;border:1px solid #fde68a;border-radius:0.25rem;padding:0.25rem 0.5rem;font-family:monospace;word-break:break-all;color:#92400e;"></code>
                        <button type="button" id="close-secret-box"
                                style="margin-top:0.5rem;color:#b45309;background:none;border:none;cursor:pointer;font-size:0.75rem;text-decoration:underline;">Dismiss</button>
                    </div>
                    <div id="clients-list" style="font-size:0.75rem;color:#374151;">
                        <p style="color:#9ca3af;font-style:italic;margin:0;">Loading clients&hellip;</p>
                    </div>
                </div>
                @endif
            </div>

            {{-- ── Column 2: Apply System Update ───────────────────────── --}}
            <div style="border:1px solid #e5e7eb;border-radius:0.625rem;padding:1rem;display:flex;flex-direction:column;gap:0.75rem;min-height:0;">
                <div id="update-pre-run">
                    <p style="font-size:0.875rem;font-weight:600;color:#111827;margin:0 0 0.375rem 0;">Apply System Update — v{{ $codeVersion }}</p>
                    <p style="font-size:0.875rem;color:#4b5563;margin:0 0 0.375rem 0;">
                        This will run <code style="font-family:monospace;background:#f3f4f6;padding:0.125rem 0.25rem;border-radius:0.25rem;">php artisan pos:deploy</code>
                        on the server — it applies database migrations, re-seeds permissions, and refreshes cached assets.
                        The operation is safe to run on a live database and takes ~30–90 seconds.
                    </p>
                    <p style="font-size:0.875rem;color:#b45309;font-weight:500;margin:0;">
                        Tip: The site remains live during the update. Individual pages will continue working.
                    </p>
                </div>

                {{-- Progress bar --}}
                <div id="update-progress-wrap" style="display:none;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.25rem;">
                        <span id="update-step-label" style="font-size:0.75rem;font-weight:500;color:#4b5563;">Preparing…</span>
                        <span id="update-pct-label" style="font-size:0.75rem;font-weight:700;color:#b45309;">0%</span>
                    </div>
                    <div style="width:100%;background:#e5e7eb;border-radius:9999px;height:0.75rem;overflow:hidden;">
                        <div id="update-progress-bar"
                             class="tw-transition-all tw-duration-700 tw-ease-in-out"
                             style="width:0%;height:0.75rem;border-radius:9999px;background:#f59e0b;transition:width 0.7s ease-in-out;"></div>
                    </div>
                </div>

                {{-- Output log --}}
                <div id="update-log-wrap"
                     style="display:none;flex:1;min-height:0;overflow-y:auto;background:#111827;padding:0.875rem;border-radius:0.5rem;">
                    <pre id="update-log"
                         style="font-size:0.75rem;color:#86efac;font-family:monospace;white-space:pre-wrap;margin:0;background:transparent;border:none;padding:0;"></pre>
                </div>

                {{-- Success / Error result --}}
                <div id="update-result" style="display:none;font-size:0.875rem;font-weight:500;border-radius:0.5rem;padding:0.75rem 1rem;"></div>
            </div>
        </div>

        {{-- Modal footer --}}
        <div style="display:flex;align-items:center;justify-content:flex-end;gap:0.625rem;padding:0.875rem 1.5rem;border-top:1px solid #e5e7eb;">
            <button id="cancel-update-btn" type="button"
                    style="padding:0.5rem 1rem;font-size:0.875rem;font-weight:500;color:#374151;background:#f3f4f6;border:none;border-radius:0.5rem;cursor:pointer;">
                Cancel
            </button>
            @if($hasUpdateServer)
            <button type="button" id="pull-update-btn"
                    style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;color:#fff;background:#2563eb;border:none;border-radius:0.5rem;cursor:pointer;white-space:nowrap;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:1rem;height:1rem;flex-shrink:0;" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/><path d="M7 11l5 5l5-5"/><path d="M12 4l0 12"/></svg>
                <span id="pull-btn-text">Pull &amp; Deploy</span>
                <span id="pull-spinner" style="display:none;">
                    <svg class="tw-animate-spin" style="width:1rem;height:1rem;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity:0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity:0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"></path>
                    </svg>
                </span>
            </button>
            @endif
            <button id="confirm-update-btn" type="button"
                    style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;color:#fff;background:#f59e0b;border:none;border-radius:0.5rem;cursor:pointer;white-space:nowrap;">
                <span id="update-btn-text">Run Update Now</span>
                <span id="update-spinner" style="display:none;">
                    <svg class="tw-animate-spin" style="width:1rem;height:1rem;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity:0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity:0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"></path>
                    </svg>
                </span>
            </button>
        </div>
    </div>
</div>
@endif

{{-- No custom toast HTML needed — using SweetAlert2 (Swal) toast --}}

@push('scripts')
<script>
(function () {
    'use strict';

    var banner       = document.getElementById('update-banner');
    var bannerMsg    = document.getElementById('update-banner-msg');
    var dismissBtn   = document.getElementById('dismiss-update-btn');
    var applyBtn     = document.getElementById('apply-update-btn');
    var modal        = document.getElementById('update-modal');
    var modalTitle   = document.getElementById('update-modal-title');
    var checkResult  = document.getElementById('update-check-result');
    var closeModal   = document.getElementById('close-update-modal');
    var cancelBtn    = document.getElementById('cancel-update-btn');
    var confirmBtn   = document.getElementById('confirm-update-btn');
    var logWrap      = document.getElementById('update-log-wrap');
    var logEl        = document.getElementById('update-log');
    var resultEl     = document.getElementById('update-result');
    var preRunEl     = document.getElementById('update-pre-run');
    var btnText      = document.getElementById('update-btn-text');
    var spinner      = document.getElementById('update-spinner');
    var checkBtn     = document.getElementById('check-update-btn');
    var checkBtnIcon = document.getElementById('check-update-icon');
    var checkBtnSpin = document.getElementById('check-update-spin');
    var pullBtn     = document.getElementById('pull-update-btn');
    var pullBtnText = document.getElementById('pull-btn-text');
    var pullSpinner = document.getElementById('pull-spinner');
    var pushAllBtn  = document.getElementById('push-all-btn');
    var buildPkgBtn  = document.getElementById('build-package-btn');
    var buildPkgText = document.getElementById('build-pkg-text');
    var buildPkgSpin = document.getElementById('build-pkg-spinner');
    var isSuperadmin = @json($isSuperadmin);
    var isRemoteOnly  = @json($remotePending && !$localPending);  // remote update available but code not yet pulled

    var csrfToken    = document.querySelector('meta[name="csrf-token"]')
                          ? document.querySelector('meta[name="csrf-token"]').content : '';

    // ── Toast helper (delegates to global window.showToast defined in javascripts.blade.php) ──
    function showToast(type, title, subtitle) {
        var msg      = title + (subtitle ? ' — ' + subtitle : '');
        var swalType = (type === 'warn') ? 'warning' : type;
        if (typeof window.showToast === 'function') {
            window.showToast(swalType, msg);
        }
    }

    // ── Dismiss banner ────────────────────────────────────────
    if (dismissBtn) {
        dismissBtn.addEventListener('click', function () {
            fetch('{{ route("superadmin.update.dismiss") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            }).catch(function () {});
            // banner removed; dismiss is a no-op
        });
    }

    // ── Open modal (from banner "Apply Update" button) ────────
    if (applyBtn && modal) {
        applyBtn.addEventListener('click', function () { resetModal(); openModal(); });
    }

    function openModal() {
        if (!modal) return;
        resetModal();
        modal.style.display = 'flex';
        if (clientsList) renderClients(); // reload client list each open (central server only)
    }
    // Expose globally so dashboard card and other pages can open this modal directly
    window.openUpdateModal = openModal;
    function closeModalFn() {
        if (!modal) return;
        modal.style.display = 'none';
    }
    if (closeModal) closeModal.addEventListener('click', closeModalFn);
    if (cancelBtn)  cancelBtn.addEventListener('click', closeModalFn);
    if (modal)      modal.addEventListener('click', function (e) { if (e.target === modal) closeModalFn(); });

    // ── "Check for Updates" header button ─────────────────────
    if (checkBtn) {
        checkBtn.addEventListener('click', function () {
            if (checkBtnIcon) checkBtnIcon.style.display = 'none';
            if (checkBtnSpin) checkBtnSpin.style.display = '';
            checkBtn.disabled = true;

            fetch('{{ route("superadmin.update.status") }}', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                restoreCheckBtn();
                if (!data.pending) {
                    showToast('success', "You're up to date!", 'Running v' + data.new_version + ' — no updates available.');
                } else {
                    // no banner to show; modal open below handles display
                    if (isSuperadmin && modal) {
                        resetModal();
                        if (modalTitle) modalTitle.textContent = 'System Update — v' + data.new_version;
                        if (checkResult) {
                            checkResult.style.display = 'block';
                            if (data.remote_pending && !data.local_pending) {
                                checkResult.className = 'tw-rounded-lg tw-px-4 tw-py-3 tw-text-sm tw-font-medium tw-bg-blue-50 tw-text-blue-900';
                                checkResult.textContent = 'Version ' + data.new_version + ' is available on the update server (you have v' + data.installed_version + '). Pull/deploy the new code files first, then click “Run Update Now” to apply migrations.';
                            } else {
                                checkResult.className = 'tw-rounded-lg tw-px-4 tw-py-3 tw-text-sm tw-font-medium tw-bg-amber-50 tw-text-amber-900';
                                checkResult.textContent = 'Update v' + data.new_version + ' found (installed: v' + data.installed_version + '). Click “Run Update Now” to apply.';
                            }
                        }
                        openModal();
                    } else {
                        showToast('warn', 'Update available — v' + data.new_version, 'Please contact your system administrator to apply this update.');
                    }
                }
            })
            .catch(function () {
                restoreCheckBtn();
                showToast('error', 'Check failed', 'Could not reach the update service. Please try again.');
            });
        });
    }

    function restoreCheckBtn() {
        if (checkBtn)     checkBtn.disabled = false;
        if (checkBtnIcon) checkBtnIcon.style.display = '';
        if (checkBtnSpin) checkBtnSpin.style.display = 'none';
    }

    // ── Real-time deploy progress via SSE ────────────────────
    var progressBar    = document.getElementById('update-progress-bar');
    var progressWrap   = document.getElementById('update-progress-wrap');
    var stepLabel      = document.getElementById('update-step-label');
    var pctLabel       = document.getElementById('update-pct-label');
    var deploySource   = null;   // active EventSource
    var deployDone     = false;  // guard against onerror firing after normal close

    function setProgress(pct, label) {
        if (progressBar) progressBar.style.width = pct + '%';
        if (pctLabel)    pctLabel.textContent = pct + '%';
        if (stepLabel && label) stepLabel.textContent = label;
    }

    function completeProgress(success) {
        if (success) {
            if (progressBar) progressBar.classList.replace('tw-bg-amber-500', 'tw-bg-green-500');
            setProgress(100, 'Complete!');
        } else {
            if (progressBar) progressBar.classList.replace('tw-bg-amber-500', 'tw-bg-red-500');
            setProgress(progressBar ? parseInt(progressBar.style.width) || 0 : 0, 'Failed — see log below');
        }
    }

    function stopDeployStream() {
        if (deploySource) { deploySource.close(); deploySource = null; }
    }

    // ── Run update (modal "Run Update Now" button) ────────────
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            confirmBtn.disabled = true;
            if (btnText)     btnText.textContent = 'Running…';
            if (spinner)     spinner.style.display = '';
            if (cancelBtn)   cancelBtn.disabled = true;
            if (closeModal)  closeModal.disabled = true;
            if (checkResult) checkResult.style.display = 'none';
            if (preRunEl)    preRunEl.style.display = 'none';
            if (progressWrap) progressWrap.style.display = '';
            if (logWrap)     logWrap.style.display = '';
            if (logEl)       logEl.textContent = '';
            setProgress(0, 'Connecting…');
            deployDone = false;
            stopDeployStream();

            deploySource = new EventSource('{{ route("superadmin.update.progress") }}');

            // Each step emits its label + pct as it starts and again (with ✓) when it finishes.
            deploySource.addEventListener('progress', function (e) {
                var d = JSON.parse(e.data);
                setProgress(d.pct, d.label);
                if (d.output && logEl) {
                    logEl.textContent += d.output;
                    logWrap.scrollTop = logWrap.scrollHeight;
                }
            });

            // stepError fires when a step throws; the stream then sends done{success:false}.
            deploySource.addEventListener('stepError', function (e) {
                var d = JSON.parse(e.data);
                setProgress(d.pct, d.label);
                if (logEl && d.output) {
                    logEl.textContent += '\n[ERROR] ' + d.output + '\n';
                    logWrap.scrollTop = logWrap.scrollHeight;
                }
            });

            deploySource.addEventListener('done', function (e) {
                deployDone = true;
                stopDeployStream();
                var d = JSON.parse(e.data);
                if (logEl && d.output) {
                    logEl.textContent += d.output;
                    logWrap.scrollTop = logWrap.scrollHeight;
                }
                if (d.success) {
                    completeProgress(true);
                    showRunResult(true, 'Update applied successfully! Reloading in 5 seconds…');
                    setTimeout(function () { window.location.reload(); }, 5000);
                } else {
                    completeProgress(false);
                    showRunResult(false, 'Update finished with errors — check the log above or server logs.');
                    resetButtons();
                }
            });

            // Network-level connection failure (not a named SSE event).
            deploySource.onerror = function () {
                if (deployDone) return;  // normal close after done event
                stopDeployStream();
                completeProgress(false);
                showRunResult(false, 'Connection lost before the update completed. Check server logs.');
                resetButtons();
            };
        });
    }

    function showRunResult(success, msg) {
        if (!resultEl) return;
        resultEl.style.display = 'block';
        resultEl.className = 'tw-text-sm tw-font-medium tw-rounded-lg tw-px-4 tw-py-3 ' +
            (success ? 'tw-bg-green-50 tw-text-green-800' : 'tw-bg-red-50 tw-text-red-800');
        resultEl.textContent = msg;
    }

    function resetButtons() {
        if (confirmBtn)  confirmBtn.disabled = false;
        if (btnText)     btnText.textContent = 'Retry';
        if (spinner)     spinner.style.display = 'none';
        if (cancelBtn)   cancelBtn.disabled = false;
        if (closeModal)  closeModal.disabled = false;
    }

    function resetModal() {
        stopDeployStream();
        deployDone = false;
        if (pullBtn)     { pullBtn.disabled = false; }
        if (pullBtnText) pullBtnText.textContent = 'Pull & Deploy';
        if (pullSpinner) pullSpinner.style.display = 'none';
        if (pushAllBtn)  pushAllBtn.disabled = false;
        if (progressWrap) progressWrap.style.display = 'none';
        if (progressBar)  { progressBar.style.width = '0%'; progressBar.className = 'tw-h-3 tw-rounded-full tw-bg-amber-500 tw-transition-all tw-duration-700 tw-ease-in-out'; }
        if (checkResult)  checkResult.style.display = 'none';
        if (preRunEl)     preRunEl.style.display = '';
        if (logWrap)      logWrap.style.display = 'none';
        if (resultEl)     { resultEl.style.display = 'none'; resultEl.textContent = ''; }
        if (logEl)        logEl.textContent = '';
        if (btnText)      btnText.textContent = 'Run Update Now';
        if (spinner)      spinner.style.display = 'none';
        if (confirmBtn)   confirmBtn.disabled = false;
        if (cancelBtn)    cancelBtn.disabled = false;
    }
    // ── Pull & Deploy from central server (client-side) ─────────
    if (pullBtn) {
        pullBtn.addEventListener('click', function () {
            pullBtn.disabled = true;
            if (pullBtnText) pullBtnText.textContent = 'Pulling…';
            if (pullSpinner) pullSpinner.style.display = '';
            if (confirmBtn)  confirmBtn.disabled = true;
            if (cancelBtn)   cancelBtn.disabled = true;
            if (closeModal)  closeModal.disabled = true;
            if (checkResult) checkResult.style.display = 'none';
            if (preRunEl)    preRunEl.style.display = 'none';
            if (progressWrap) progressWrap.style.display = '';
            if (logWrap)     logWrap.style.display = '';
            if (logEl)       logEl.textContent = '';
            setProgress(0, 'Connecting to central server…');
            deployDone = false;
            stopDeployStream();

            deploySource = new EventSource('{{ route("superadmin.update.pull-progress") }}');

            deploySource.addEventListener('progress', function (e) {
                var d = JSON.parse(e.data);
                setProgress(d.pct, d.label);
                if (d.output && logEl) { logEl.textContent += d.output; logWrap.scrollTop = logWrap.scrollHeight; }
            });

            deploySource.addEventListener('stepError', function (e) {
                var d = JSON.parse(e.data);
                setProgress(d.pct, d.label);
                if (logEl && d.output) { logEl.textContent += '\n[ERROR] ' + d.output + '\n'; logWrap.scrollTop = logWrap.scrollHeight; }
            });

            deploySource.addEventListener('done', function (e) {
                deployDone = true;
                stopDeployStream();
                var d = JSON.parse(e.data);
                if (logEl && d.output) { logEl.textContent += d.output; logWrap.scrollTop = logWrap.scrollHeight; }
                if (d.success) {
                    completeProgress(true);
                    showRunResult(true, 'Update pulled and applied! Reloading in 5 seconds…');
                    setTimeout(function () { window.location.reload(); }, 5000);
                } else {
                    completeProgress(false);
                    showRunResult(false, 'Pull update finished with errors — check the log or server logs.');
                    resetButtons();
                }
            });

            deploySource.onerror = function () {
                if (deployDone) return;
                stopDeployStream();
                completeProgress(false);
                showRunResult(false, 'Connection lost during pull. Check server logs.');
                resetButtons();
            };
        });
    }

    // ── Build release package + push to all clients ─────────────
    if (buildPkgBtn) {
        buildPkgBtn.addEventListener('click', function () {
            Swal.fire({
                title: 'Build &amp; Push v{{ config("author.app_version") }}?',
                text: 'Package the current codebase and push it to all registered client servers.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, build &amp; push',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#6b7280',
                reverseButtons: true,
                didOpen: function () {
                    var c = document.querySelector('.swal2-container');
                    if (c) c.style.zIndex = '20000';
                },
            }).then(function (result) {
                if (! result.isConfirmed) return;

                buildPkgBtn.disabled = true;
                if (buildPkgText) buildPkgText.textContent = 'Building…';
            if (buildPkgSpin) buildPkgSpin.style.display = '';
            if (checkResult) checkResult.style.display = 'none';
            if (preRunEl)    preRunEl.style.display = 'none';
            if (logWrap)     logWrap.style.display = '';
            if (logEl)       logEl.textContent = '';

            // Phase 1: stream build progress via SSE
            deployDone = false;
            stopDeployStream();
            deploySource = new EventSource('{{ $isSuperadmin ? route("superadmin.update.build-package") : "" }}');

            deploySource.addEventListener('progress', function (e) {
                var d = JSON.parse(e.data);
                if (logEl) { logEl.textContent += d.message + '\n'; logWrap.scrollTop = logWrap.scrollHeight; }
            });

            deploySource.addEventListener('done', function (e) {
                stopDeployStream();
                var d = JSON.parse(e.data);
                if (! d.success) {
                    showRunResult(false, 'Build failed: ' + (d.message || 'unknown error'));
                    resetBuildBtn();
                    return;
                }
                if (logEl) {
                    logEl.textContent += 'Package v' + d.version + ' ready (' + d.size_kb + ' KB).\nPushing to clients…\n';
                    logWrap.scrollTop = logWrap.scrollHeight;
                }
                if (buildPkgText) buildPkgText.textContent = 'Pushing…';

                // Phase 2: push to all clients via SSE
                deployDone = false;
                deploySource = new EventSource('{{ route("superadmin.update.push-all") }}');

                deploySource.addEventListener('clientStart', function (e) {
                    var d = JSON.parse(e.data);
                    if (logEl) { logEl.textContent += 'Pushing to ' + d.name + '…\n'; logWrap.scrollTop = logWrap.scrollHeight; }
                });
                deploySource.addEventListener('clientResult', function (e) {
                    var d = JSON.parse(e.data);
                    if (logEl) {
                        logEl.textContent += '  ' + (d.success ? '✓' : '✗') + ' ' + d.name + ': ' + d.message + '\n';
                        logWrap.scrollTop = logWrap.scrollHeight;
                    }
                    if (typeof renderClients === 'function') renderClients();
                });
                deploySource.addEventListener('done', function (e) {
                    deployDone = true;
                    stopDeployStream();
                    var d = JSON.parse(e.data);
                    if (! d.success) {
                        showRunResult(false, 'Package built, but ' + (d.failed || 0) + ' client(s) failed. Check log above.');
                    } else if (d.total === 0) {
                        showRunResult(true, 'Package built successfully. ' + (d.message || 'No active clients registered.'));
                    } else {
                        showRunResult(true, 'Package built and pushed to ' + d.succeeded + '/' + d.total + ' client(s). They will self-update in the background.');
                    }
                    resetBuildBtn();
                });
                deploySource.onerror = function () {
                    if (deployDone) return;
                    stopDeployStream();
                    showRunResult(false, 'Package built — push stream lost. Check server logs.');
                    resetBuildBtn();
                };
            });

            deploySource.onerror = function () {
                stopDeployStream();
                showRunResult(false, 'Build stream lost. Check server logs.');
                resetBuildBtn();
            };
            }); // end Swal.then
        });
    }

    function resetBuildBtn() {
        if (buildPkgBtn)  buildPkgBtn.disabled = false;
        if (buildPkgText) buildPkgText.textContent = 'Build \u0026 Push All Clients';
        if (buildPkgSpin) buildPkgSpin.style.display = 'none';
    }

    // ── Push to all clients (central server) ────────────────────
    if (pushAllBtn) {
        pushAllBtn.addEventListener('click', function () {
            if (! confirm('Send the update trigger to all active clients?')) return;
            pushAllBtn.disabled = true;
            if (cancelBtn)  cancelBtn.disabled = true;
            if (closeModal) closeModal.disabled = true;
            if (checkResult) checkResult.style.display = 'none';
            if (preRunEl)   preRunEl.style.display = 'none';
            if (logWrap)    logWrap.style.display = '';
            if (logEl)      logEl.textContent = 'Pushing to clients…\n';
            deployDone = false;
            stopDeployStream();

            deploySource = new EventSource('{{ route("superadmin.update.push-all") }}');

            deploySource.addEventListener('clientStart', function (e) {
                var d = JSON.parse(e.data);
                if (logEl) { logEl.textContent += 'Pushing to ' + d.name + '…\n'; logWrap.scrollTop = logWrap.scrollHeight; }
            });

            deploySource.addEventListener('clientResult', function (e) {
                var d = JSON.parse(e.data);
                if (logEl) {
                    logEl.textContent += '  ' + (d.success ? '✓' : '✗') + ' ' + d.name + ': ' + d.message + '\n';
                    logWrap.scrollTop = logWrap.scrollHeight;
                }
                renderClients(); // refresh the clients list
            });

            deploySource.addEventListener('done', function (e) {
                deployDone = true;
                stopDeployStream();
                var d = JSON.parse(e.data);
                if (d.success) {
                    showRunResult(true, 'Pushed to ' + d.succeeded + '/' + d.total + ' clients. They will apply the update in the background.');
                } else {
                    showRunResult(false, (d.failed || 0) + ' client(s) failed. Check the log above.');
                }
                resetButtons();
            });

            deploySource.onerror = function () {
                if (deployDone) return;
                stopDeployStream();
                showRunResult(false, 'Push stream lost. Check server logs.');
                resetButtons();
            };
        });
    }

    // ── Client registry management ─────────────────────────────
    var addClientBtn       = document.getElementById('add-client-btn');
    var addClientForm      = document.getElementById('add-client-form');
    var saveClientBtn      = document.getElementById('save-client-btn');
    var cancelAddClientBtn = document.getElementById('cancel-add-client-btn');
    var newClientName      = document.getElementById('new-client-name');
    var newClientUrl       = document.getElementById('new-client-url');
    var clientsList        = document.getElementById('clients-list');
    var clientSecretBox    = document.getElementById('client-secret-box');
    var clientSecretValue  = document.getElementById('client-secret-value');
    var closeSecretBox     = document.getElementById('close-secret-box');

    if (addClientBtn)       addClientBtn.addEventListener('click', function () { addClientForm && (addClientForm.style.display = 'flex'); });
    if (cancelAddClientBtn) cancelAddClientBtn.addEventListener('click', function () { addClientForm && (addClientForm.style.display = 'none'); });
    if (closeSecretBox)     closeSecretBox.addEventListener('click', function () { clientSecretBox && (clientSecretBox.style.display = 'none'); });

    if (saveClientBtn) {
        saveClientBtn.addEventListener('click', function () {
            var name = newClientName ? newClientName.value.trim() : '';
            var url  = newClientUrl  ? newClientUrl.value.trim()  : '';
            if (! name || ! url) { alert('Name and URL are required.'); return; }
            saveClientBtn.disabled = true;

            fetch('{{ route("superadmin.update.clients.store") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ name: name, url: url }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                saveClientBtn.disabled = false;
                if (data.success) {
                    if (newClientName) newClientName.value = '';
                    if (newClientUrl)  newClientUrl.value  = '';
                    addClientForm && (addClientForm.style.display = 'none');
                    if (clientSecretValue) clientSecretValue.textContent = 'UPDATE_WEBHOOK_SECRET=' + data.webhook_secret;
                    clientSecretBox && (clientSecretBox.style.display = '');
                    renderClients();
                } else {
                    alert(data.message || 'Failed to add client.');
                }
            })
            .catch(function () { saveClientBtn.disabled = false; alert('Request failed.'); });
        });
    }

    function renderClients() {
        if (! clientsList) return;
        fetch('{{ route("superadmin.update.clients") }}', { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var clients = data.clients || [];
            if (clients.length === 0) {
                clientsList.innerHTML = '<p class="tw-text-gray-400 tw-italic">No clients registered yet.</p>';
                return;
            }
            clientsList.innerHTML = clients.map(function (c) {
                var status = c.last_push_status
                    ? '<span class="tw-font-semibold ' + (c.last_push_status === 'success' ? 'tw-text-green-600' : c.last_push_status === 'failed' ? 'tw-text-red-600' : 'tw-text-amber-600') + '">' + c.last_push_status + '</span>'
                    : '<span class="tw-text-gray-400">never pushed</span>';
                return '<div class="tw-flex tw-items-center tw-justify-between tw-gap-2 tw-py-1 tw-border-b tw-border-gray-100">' +
                    '<div class="tw-min-w-0">' +
                    '<span class="tw-font-medium">' + escHtml(c.name) + '</span> ' +
                    '<span class="tw-text-gray-400">' + escHtml(c.url) + '</span>' +
                    ' &mdash; v' + escHtml(c.last_version || '?') + ' &mdash; ' + status +
                    '</div>' +
                    '<div class="tw-flex tw-items-center tw-gap-1 tw-shrink-0">' +
                    '<button class="tw-text-xs tw-font-medium tw-text-indigo-600 hover:tw-underline tw-client-push" data-id="' + c.id + '" data-name="' + escHtml(c.name) + '">Push</button>' +
                    '<button class="tw-text-xs tw-font-medium tw-text-red-500 hover:tw-underline tw-client-delete" data-id="' + c.id + '">Remove</button>' +
                    '</div>' +
                    '</div>';
            }).join('');

            clientsList.querySelectorAll('.tw-client-push').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (! confirm('Push update trigger to ' + btn.dataset.name + '?')) return;
                    btn.disabled = true;
                    var id = btn.dataset.id;
                    var url = '{{ route("superadmin.update.clients.push", ["id" => "__ID__"]) }}'.replace('__ID__', id);
                    fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) { showToast(data.success ? 'success' : 'error', data.message || (data.success ? 'Pushed' : 'Failed')); renderClients(); })
                    .catch(function () { btn.disabled = false; });
                });
            });

            clientsList.querySelectorAll('.tw-client-delete').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (! confirm('Remove this client?')) return;
                    var id = btn.dataset.id;
                    var url = '{{ route("superadmin.update.clients.destroy", ["id" => "__ID__"]) }}'.replace('__ID__', id);
                    fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function () { renderClients(); })
                    .catch(function () {});
                });
            });
        })
        .catch(function () { if (clientsList) clientsList.innerHTML = '<p class="tw-text-red-400">Could not load clients.</p>'; });
    }

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Background poll every 10 min ──────────────────────────
    setInterval(function () {
        fetch('{{ route("superadmin.update.status") }}', { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            // banner removed; update badge refreshes on next page load
        })
        .catch(function () {});
    }, 10 * 60 * 1000);
})();
</script>
@endpush
{{-- NOTE: Uses @push('scripts') — must match @stack('scripts') in layouts/partials/javascripts.blade.php --}}
