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

{{-- ─── Update available banner (hidden via class when no update/snoozed) ── --}}
<div id="update-banner"
     class="no-print tw-w-full tw-flex tw-items-center tw-justify-between tw-gap-3 tw-px-5 tw-py-2
            tw-bg-amber-500 tw-border-b tw-border-amber-600 tw-text-gray-900 tw-text-sm tw-font-medium
            {{ ($updatePending && !$isSnoozed) ? '' : 'tw-hidden' }}"
     style="z-index:9999;">

    <div class="tw-flex tw-items-center tw-gap-2 tw-flex-1 tw-min-w-0">
        <svg xmlns="http://www.w3.org/2000/svg" class="tw-shrink-0 tw-size-4" viewBox="0 0 24 24"
             stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/>
            <path d="M12 8v4"/>
            <path d="M12 16v.01"/>
        </svg>
        <span id="update-banner-msg">
            @if($isSuperadmin)
                @if($remotePending && !$localPending)
                    New version <strong>{{ $displayVersion }}</strong> is available &mdash; pull the latest code, then click Apply Update.
                @else
                    System update available &mdash; version <strong>{{ $displayVersion }}</strong> is ready to install.
                @endif
            @else
                A system update is available (v{{ $displayVersion }}). Please contact your administrator.
            @endif
        </span>
    </div>

    <div class="tw-flex tw-items-center tw-gap-2 tw-shrink-0">
        @if($isSuperadmin)
            <button type="button" id="apply-update-btn"
                    class="tw-inline-flex tw-items-center tw-gap-1.5 tw-rounded-md tw-bg-white tw-px-3 tw-py-1
                           tw-text-xs tw-font-semibold tw-text-amber-900 tw-shadow hover:tw-bg-amber-50 tw-transition-colors tw-border tw-border-white/60"
                    style="background-color:#ffffff !important;color:#78350f !important;border-color:#ffffff !important;">
                <svg xmlns="http://www.w3.org/2000/svg" class="tw-size-3.5" viewBox="0 0 24 24"
                     stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/>
                    <path d="M7 11l5 5l5 -5"/>
                    <path d="M12 4l0 12"/>
                </svg>
                Apply Update
            </button>
        @endif

        <button type="button" id="dismiss-update-btn" title="Dismiss for 24 hours"
                class="tw-inline-flex tw-items-center tw-rounded-md tw-bg-amber-700 tw-px-2 tw-py-1
                       tw-text-xs tw-font-medium tw-text-white hover:tw-bg-amber-800 tw-transition-colors"
                style="background-color:#b45309 !important;color:#ffffff !important;">
            Dismiss
        </button>
    </div>
</div>

{{-- ─── Apply Update Modal (always rendered for superadmins) ───────────── --}}
@if($isSuperadmin)
<div id="update-modal"
     class="tw-fixed tw-inset-0 tw-z-[10000] tw-hidden tw-items-center tw-justify-center tw-bg-black/60 tw-backdrop-blur-sm">
    <div class="tw-bg-white tw-rounded-xl tw-shadow-2xl tw-w-full tw-max-w-2xl tw-mx-4 tw-flex tw-flex-col"
         style="max-height:85vh;">
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
                    Apply System Update — v{{ $codeVersion }}
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
        <div class="tw-px-6 tw-py-4 tw-flex tw-flex-col tw-gap-4 tw-flex-1 tw-overflow-hidden">
            {{-- Result shown when Check for Updates finds a new version --}}
            <div id="update-check-result" class="tw-hidden tw-rounded-lg tw-px-4 tw-py-3 tw-text-sm tw-font-medium"></div>

            <div id="update-pre-run">
                <p class="tw-text-sm tw-text-gray-600">
                    This will run <code class="tw-font-mono tw-bg-gray-100 tw-px-1 tw-rounded">php artisan pos:deploy</code>
                    on the server — it applies database migrations, re-seeds permissions, and refreshes cached assets.
                    The operation is safe to run on a live database and takes ~30–90 seconds.
                </p>
                <p class="tw-text-sm tw-text-amber-700 tw-font-medium tw-mt-2">
                    Tip: The site remains live during the update. Individual pages will continue working.
                </p>
            </div>

            {{-- Progress bar --}}
            <div id="update-progress-wrap" class="tw-hidden">
                <div class="tw-flex tw-items-center tw-justify-between tw-mb-1">
                    <span id="update-step-label" class="tw-text-xs tw-font-medium tw-text-gray-600">Preparing…</span>
                    <span id="update-pct-label" class="tw-text-xs tw-font-bold tw-text-amber-700">0%</span>
                </div>
                <div class="tw-w-full tw-bg-gray-200 tw-rounded-full tw-h-3 tw-overflow-hidden">
                    <div id="update-progress-bar"
                         class="tw-h-3 tw-rounded-full tw-bg-amber-500 tw-transition-all tw-duration-700 tw-ease-in-out"
                         style="width:0%;"></div>
                </div>
            </div>

            {{-- Output log --}}
            <div id="update-log-wrap"
                 class="tw-hidden tw-flex-1 tw-overflow-y-auto tw-rounded-lg tw-bg-gray-900 tw-p-4 tw-min-h-0"
                 style="min-height:200px; max-height:300px;">
                <pre id="update-log"
                     class="tw-text-xs tw-text-green-300 tw-font-mono tw-whitespace-pre-wrap tw-m-0"></pre>
            </div>

            {{-- Success / Error state --}}
            <div id="update-result" class="tw-hidden tw-text-sm tw-font-medium tw-rounded-lg tw-px-4 tw-py-3"></div>

            @if($hasClientRegistry)
            {{-- Registered Clients panel (central server only) --}}
            <div id="update-clients-wrap" class="tw-border-t tw-border-gray-100 tw-pt-4">
                <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
                    <span class="tw-text-xs tw-font-semibold tw-text-gray-500 tw-uppercase tw-tracking-wide">Registered Client Servers</span>
                    <button type="button" id="add-client-btn"
                            class="tw-inline-flex tw-items-center tw-gap-1 tw-text-xs tw-font-medium tw-text-amber-700 hover:tw-text-amber-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="tw-size-3.5" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                        Add Client
                    </button>
                </div>

                {{-- Add client form (hidden by default) --}}
                <div id="add-client-form" class="tw-hidden tw-flex tw-gap-2 tw-mb-3">
                    <input id="new-client-name" type="text" placeholder="Name" class="tw-flex-1 tw-text-xs tw-border tw-border-gray-300 tw-rounded tw-px-2 tw-py-1">
                    <input id="new-client-url"  type="url"  placeholder="https://client.example.com" class="tw-flex-[2] tw-text-xs tw-border tw-border-gray-300 tw-rounded tw-px-2 tw-py-1">
                    <button type="button" id="save-client-btn" class="tw-text-xs tw-font-medium tw-bg-amber-500 tw-text-white tw-rounded tw-px-3 tw-py-1 hover:tw-bg-amber-600">Save</button>
                    <button type="button" id="cancel-add-client-btn" class="tw-text-xs tw-text-gray-500 hover:tw-text-gray-700">Cancel</button>
                </div>

                {{-- Webhook secret modal (shown once after client creation) --}}
                <div id="client-secret-box" class="tw-hidden tw-rounded tw-bg-amber-50 tw-border tw-border-amber-300 tw-p-3 tw-mb-3 tw-text-xs">
                    <p class="tw-font-semibold tw-text-amber-800 tw-mb-1">Set this on the client server's <code>.env</code> — it will not be shown again:</p>
                    <code id="client-secret-value" class="tw-block tw-bg-white tw-border tw-border-amber-200 tw-rounded tw-px-2 tw-py-1 tw-font-mono tw-break-all tw-text-amber-900"></code>
                    <button type="button" id="close-secret-box" class="tw-mt-2 tw-text-amber-700 hover:tw-underline">Dismiss</button>
                </div>

                <div id="clients-list" class="tw-space-y-1 tw-text-xs tw-text-gray-700">
                    <p class="tw-text-gray-400 tw-italic">Loading clients&hellip;</p>
                </div>
            </div>
            @endif
        </div>

        {{-- Modal footer --}}
        <div class="tw-flex tw-items-center tw-justify-between tw-gap-3 tw-px-6 tw-py-4 tw-border-t tw-border-gray-200">
            <div class="tw-flex tw-items-center tw-gap-2">
                {{-- Build Package button — always visible to superadmin --}}
                <button type="button" id="build-package-btn"
                        class="tw-inline-flex tw-items-center tw-gap-1.5 tw-px-3 tw-py-2 tw-text-xs tw-font-semibold
                               tw-text-white tw-bg-green-600 hover:tw-bg-green-700 tw-rounded-lg tw-transition-colors
                               disabled:tw-opacity-50 disabled:tw-pointer-events-none"
                        style="background-color:#16a34a !important;color:#ffffff !important;"
                        title="Package current codebase into a distributable zip, then push to all clients">
                    <svg xmlns="http://www.w3.org/2000/svg" class="tw-size-3.5" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l8 4.5v9l-8 4.5l-8-4.5v-9l8-4.5"/><path d="M12 12l8-4.5"/><path d="M12 12v9"/><path d="M12 12l-8-4.5"/></svg>
                    <span id="build-pkg-text">Build &amp; Push All Clients</span>
                    <span id="build-pkg-spinner" class="tw-hidden">
                        <svg class="tw-animate-spin tw-size-3.5 tw-text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="tw-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="tw-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"></path>
                        </svg>
                    </span>
                </button>
                @if($hasClientRegistry)
                <button type="button" id="push-all-btn"
                        class="tw-inline-flex tw-items-center tw-gap-1.5 tw-px-3 tw-py-2 tw-text-xs tw-font-semibold
                               tw-text-white tw-bg-indigo-500 hover:tw-bg-indigo-600 tw-rounded-lg tw-transition-colors
                               disabled:tw-opacity-50 disabled:tw-pointer-events-none"
                        style="background-color:#6366f1 !important;color:#ffffff !important;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="tw-size-3.5" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/><path d="M7 11l5-5l5 5"/><path d="M12 4v12"/></svg>
                    Push All Clients
                </button>
                @endif
            </div>
            <div class="tw-flex tw-items-center tw-gap-2">
                <button id="cancel-update-btn" type="button"
                        class="tw-px-4 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-700 tw-bg-gray-100
                               hover:tw-bg-gray-200 tw-rounded-lg tw-transition-colors"
                        style="background-color:#f3f4f6 !important;color:#374151 !important;">
                    Cancel
                </button>
                @if($hasUpdateServer)
                <button type="button" id="pull-update-btn"
                        class="tw-inline-flex tw-items-center tw-gap-2 tw-px-4 tw-py-2 tw-text-sm tw-font-semibold
                               tw-text-white tw-bg-blue-600 hover:tw-bg-blue-700 tw-rounded-lg tw-transition-colors
                               disabled:tw-opacity-50 disabled:tw-pointer-events-none"
                        style="background-color:#2563eb !important;color:#ffffff !important;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="tw-size-4" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/><path d="M7 11l5 5l5-5"/><path d="M12 4l0 12"/></svg>
                    <span id="pull-btn-text">Pull &amp; Deploy</span>
                    <span id="pull-spinner" class="tw-hidden">
                        <svg class="tw-animate-spin tw-size-4 tw-text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="tw-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="tw-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"></path>
                        </svg>
                    </span>
                </button>
                @endif
                <button id="confirm-update-btn" type="button"
                        class="tw-inline-flex tw-items-center tw-gap-2 tw-px-4 tw-py-2 tw-text-sm tw-font-semibold
                               tw-text-white tw-bg-amber-500 hover:tw-bg-amber-600 tw-rounded-lg tw-transition-colors
                               disabled:tw-opacity-50 disabled:tw-pointer-events-none"
                        style="background-color:#f59e0b !important;color:#ffffff !important;">
                    <span id="update-btn-text">Run Update Now</span>
                    <span id="update-spinner" class="tw-hidden">
                        <svg class="tw-animate-spin tw-size-4 tw-text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="tw-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="tw-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"></path>
                        </svg>
                    </span>
                </button>
            </div>
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
            if (banner) banner.classList.add('tw-hidden');
        });
    }

    // ── Open modal (from banner "Apply Update" button) ────────
    if (applyBtn && modal) {
        applyBtn.addEventListener('click', function () { resetModal(); openModal(); });
    }

    function openModal() {
        if (!modal) return;
        modal.classList.remove('tw-hidden');
        modal.classList.add('tw-flex');
        if (clientsList) renderClients(); // reload client list each open (central server only)
    }
    function closeModalFn() {
        if (!modal) return;
        modal.classList.add('tw-hidden');
        modal.classList.remove('tw-flex');
    }
    if (closeModal) closeModal.addEventListener('click', closeModalFn);
    if (cancelBtn)  cancelBtn.addEventListener('click', closeModalFn);
    if (modal)      modal.addEventListener('click', function (e) { if (e.target === modal) closeModalFn(); });

    // ── "Check for Updates" header button ─────────────────────
    if (checkBtn) {
        checkBtn.addEventListener('click', function () {
            if (checkBtnIcon) checkBtnIcon.classList.add('tw-hidden');
            if (checkBtnSpin) checkBtnSpin.classList.remove('tw-hidden');
            checkBtn.disabled = true;

            fetch('{{ route("superadmin.update.status") }}', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                restoreCheckBtn();
                if (!data.pending) {
                    showToast('success', "You're up to date!", 'Running v' + data.new_version + ' — no updates available.');
                } else {
                    // Show/unhide the banner with refreshed text
                    if (banner) {
                        banner.classList.remove('tw-hidden');
                        if (bannerMsg) {
                            if (isSuperadmin) {
                                var msg = data.remote_pending && !data.local_pending
                                    ? 'New version <strong>' + data.new_version + '</strong> available — pull the latest code, then click Apply Update.'
                                    : 'System update available — version <strong>' + data.new_version + '</strong> is ready to install.';
                                bannerMsg.innerHTML = msg;
                            } else {
                                bannerMsg.textContent = 'A system update is available (v' + data.new_version + '). Please contact your administrator.';
                            }
                        }
                    }
                    if (isSuperadmin && modal) {
                        resetModal();
                        if (modalTitle) modalTitle.textContent = 'Apply System Update — v' + data.new_version;
                        if (checkResult) {
                            checkResult.classList.remove('tw-hidden');
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
        if (checkBtnIcon) checkBtnIcon.classList.remove('tw-hidden');
        if (checkBtnSpin) checkBtnSpin.classList.add('tw-hidden');
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
            if (spinner)     spinner.classList.remove('tw-hidden');
            if (cancelBtn)   cancelBtn.disabled = true;
            if (closeModal)  closeModal.disabled = true;
            if (checkResult) checkResult.classList.add('tw-hidden');
            if (preRunEl)    preRunEl.classList.add('tw-hidden');
            if (progressWrap) progressWrap.classList.remove('tw-hidden');
            if (logWrap)     logWrap.classList.remove('tw-hidden');
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
                    if (banner) banner.classList.add('tw-hidden');
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
        resultEl.className = 'tw-text-sm tw-font-medium tw-rounded-lg tw-px-4 tw-py-3 ' +
            (success ? 'tw-bg-green-50 tw-text-green-800' : 'tw-bg-red-50 tw-text-red-800');
        resultEl.textContent = msg;
    }

    function resetButtons() {
        if (confirmBtn)  confirmBtn.disabled = false;
        if (btnText)     btnText.textContent = 'Retry';
        if (spinner)     spinner.classList.add('tw-hidden');
        if (cancelBtn)   cancelBtn.disabled = false;
        if (closeModal)  closeModal.disabled = false;
    }

    function resetModal() {
        stopDeployStream();
        deployDone = false;
        if (pullBtn)     { pullBtn.disabled = false; }
        if (pullBtnText) pullBtnText.textContent = 'Pull & Deploy';
        if (pullSpinner) pullSpinner.classList.add('tw-hidden');
        if (pushAllBtn)  pushAllBtn.disabled = false;
        if (progressWrap) progressWrap.classList.add('tw-hidden');
        if (progressBar)  { progressBar.style.width = '0%'; progressBar.className = 'tw-h-3 tw-rounded-full tw-bg-amber-500 tw-transition-all tw-duration-700 tw-ease-in-out'; }
        if (checkResult)  checkResult.classList.add('tw-hidden');
        if (preRunEl)     preRunEl.classList.remove('tw-hidden');
        if (logWrap)      logWrap.classList.add('tw-hidden');
        if (resultEl)     resultEl.className = 'tw-hidden';
        if (logEl)        logEl.textContent = '';
        if (btnText)      btnText.textContent = 'Run Update Now';
        if (spinner)      spinner.classList.add('tw-hidden');
        if (confirmBtn)   confirmBtn.disabled = false;
        if (cancelBtn)    cancelBtn.disabled = false;
    }
    // ── Pull & Deploy from central server (client-side) ─────────
    if (pullBtn) {
        pullBtn.addEventListener('click', function () {
            pullBtn.disabled = true;
            if (pullBtnText) pullBtnText.textContent = 'Pulling…';
            if (pullSpinner) pullSpinner.classList.remove('tw-hidden');
            if (confirmBtn)  confirmBtn.disabled = true;
            if (cancelBtn)   cancelBtn.disabled = true;
            if (closeModal)  closeModal.disabled = true;
            if (checkResult) checkResult.classList.add('tw-hidden');
            if (preRunEl)    preRunEl.classList.add('tw-hidden');
            if (progressWrap) progressWrap.classList.remove('tw-hidden');
            if (logWrap)     logWrap.classList.remove('tw-hidden');
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
                    if (banner) banner.classList.add('tw-hidden');
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
            if (! confirm('This will package the current codebase (v{{ config("author.app_version") }}) and push it to all registered client servers. Continue?')) return;

            buildPkgBtn.disabled = true;
            if (buildPkgText) buildPkgText.textContent = 'Building…';
            if (buildPkgSpin) buildPkgSpin.classList.remove('tw-hidden');
            if (checkResult) checkResult.classList.add('tw-hidden');
            if (preRunEl)    preRunEl.classList.add('tw-hidden');
            if (logWrap)     logWrap.classList.remove('tw-hidden');
            if (logEl)       logEl.textContent = 'Building release package…\n';

            fetch('{{ route("superadmin.update.build-package") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (! data.success) {
                    showRunResult(false, 'Build failed: ' + (data.message || 'unknown error'));
                    resetBuildBtn();
                    return;
                }
                if (logEl) {
                    logEl.textContent += data.message + '\n';
                    logEl.textContent += 'Package v' + data.version + ' ready (' + data.size_kb + ' KB). Pushing to clients…\n';
                    logWrap.scrollTop = logWrap.scrollHeight;
                }
                if (buildPkgText) buildPkgText.textContent = 'Pushing…';

                // Now push to all clients via SSE
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
                    if (typeof renderClients === 'function') renderClients();
                });
                deploySource.addEventListener('done', function (e) {
                    deployDone = true;
                    stopDeployStream();
                    var d = JSON.parse(e.data);
                    if (d.success) {
                        showRunResult(true, 'Package built and pushed to ' + d.succeeded + '/' + d.total + ' client(s). They will self-update in the background.');
                    } else {
                        showRunResult(false, 'Package built, but ' + (d.failed || 0) + ' client(s) failed. Check log above.');
                    }
                    resetBuildBtn();
                });
                deploySource.onerror = function () {
                    if (deployDone) return;
                    stopDeployStream();
                    showRunResult(false, 'Package built — push stream lost. Check server logs.');
                    resetBuildBtn();
                };
            })
            .catch(function (err) {
                showRunResult(false, 'Build request failed: ' + err.message);
                resetBuildBtn();
            });
        });
    }

    function resetBuildBtn() {
        if (buildPkgBtn)  buildPkgBtn.disabled = false;
        if (buildPkgText) buildPkgText.textContent = 'Build \u0026 Push All Clients';
        if (buildPkgSpin) buildPkgSpin.classList.add('tw-hidden');
    }

    // ── Push to all clients (central server) ────────────────────
    if (pushAllBtn) {
        pushAllBtn.addEventListener('click', function () {
            if (! confirm('Send the update trigger to all active clients?')) return;
            pushAllBtn.disabled = true;
            if (cancelBtn)  cancelBtn.disabled = true;
            if (closeModal) closeModal.disabled = true;
            if (checkResult) checkResult.classList.add('tw-hidden');
            if (preRunEl)   preRunEl.classList.add('tw-hidden');
            if (logWrap)    logWrap.classList.remove('tw-hidden');
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

    if (addClientBtn)       addClientBtn.addEventListener('click', function () { addClientForm && addClientForm.classList.remove('tw-hidden'); });
    if (cancelAddClientBtn) cancelAddClientBtn.addEventListener('click', function () { addClientForm && addClientForm.classList.add('tw-hidden'); });
    if (closeSecretBox)     closeSecretBox.addEventListener('click', function () { clientSecretBox && clientSecretBox.classList.add('tw-hidden'); });

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
                    addClientForm && addClientForm.classList.add('tw-hidden');
                    if (clientSecretValue) clientSecretValue.textContent = 'UPDATE_WEBHOOK_SECRET=' + data.webhook_secret;
                    clientSecretBox && clientSecretBox.classList.remove('tw-hidden');
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
            if (data.pending && banner && banner.classList.contains('tw-hidden')) {
                banner.classList.remove('tw-hidden');
            }
        })
        .catch(function () {});
    }, 10 * 60 * 1000);
})();
</script>
@endpush
{{-- NOTE: Uses @push('scripts') — must match @stack('scripts') in layouts/partials/javascripts.blade.php --}}
