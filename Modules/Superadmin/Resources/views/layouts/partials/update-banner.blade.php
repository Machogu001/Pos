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

    // Central-server: show registry/tools when this instance is not configured as a client.
    $hasClientRegistry = $isSuperadmin && ! $hasUpdateServer;
@endphp

{{-- ─── Apply Update Modal (always rendered for superadmins) ───────────── --}}
@if($isSuperadmin)
<div id="update-modal"
    style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;background:rgba(15,23,42,0.08);backdrop-filter:none;">
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

                <div id="release-packages-wrap" style="padding-top:0.75rem;border-top:1px solid #f3f4f6;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                        <span style="font-size:0.6875rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;">Stored Release Packages</span>
                        <button type="button" id="refresh-packages-btn"
                                style="font-size:0.75rem;font-weight:500;color:#2563eb;background:none;border:none;cursor:pointer;">
                            Refresh
                        </button>
                    </div>
                    <div style="display:flex;gap:0.5rem;align-items:center;">
                        <select id="release-package-select"
                                style="flex:1;min-width:10rem;font-size:0.75rem;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.35rem 0.5rem;">
                            <option value="">Loading packages...</option>
                        </select>
                        <button type="button" id="delete-package-btn"
                                style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.75rem;font-weight:600;background:#dc2626;color:#fff;border:none;border-radius:0.375rem;padding:0.35rem 0.625rem;cursor:pointer;">
                            Delete Selected
                        </button>
                    </div>
                </div>

                <div id="download-token-wrap" style="padding-top:0.75rem;border-top:1px solid #f3f4f6;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                        <span style="font-size:0.6875rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;">Client Pull Auth Token</span>
                        <button type="button" id="regen-download-token-btn"
                                style="font-size:0.75rem;font-weight:600;color:#fff;background:#b45309;border:none;border-radius:0.375rem;padding:0.35rem 0.625rem;cursor:pointer;">
                            Generate / Regenerate
                        </button>
                    </div>
                    <p id="download-token-status" style="font-size:0.75rem;color:#6b7280;margin:0 0 0.5rem 0;">Checking token status...</p>
                    <div style="display:flex;gap:0.5rem;align-items:center;">
                        <input id="download-token-value" type="text" readonly
                               placeholder="Generate a token to reveal it once"
                               style="flex:1;min-width:10rem;font-size:0.75rem;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.35rem 0.5rem;background:#f9fafb;color:#111827;">
                        <button type="button" id="show-download-token-btn"
                                style="font-size:0.75rem;font-weight:600;background:#6b7280;color:#fff;border:none;border-radius:0.375rem;padding:0.35rem 0.625rem;cursor:pointer;">
                            Show
                        </button>
                        <button type="button" id="copy-download-token-btn"
                                style="font-size:0.75rem;font-weight:600;background:#2563eb;color:#fff;border:none;border-radius:0.375rem;padding:0.35rem 0.625rem;cursor:pointer;">
                            Copy
                        </button>
                    </div>
                    <p style="font-size:0.75rem;color:#4b5563;margin:0.5rem 0 0 0;">
                        Set this value on each client server as <strong>UPDATE_AUTH_TOKEN</strong>.
                    </p>
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
    var releasePackageSelect = document.getElementById('release-package-select');
    var deletePackageBtn     = document.getElementById('delete-package-btn');
    var refreshPackagesBtn   = document.getElementById('refresh-packages-btn');
    var tokenStatusEl        = document.getElementById('download-token-status');
    var tokenValueEl         = document.getElementById('download-token-value');
    var showTokenBtn         = document.getElementById('show-download-token-btn');
    var regenTokenBtn        = document.getElementById('regen-download-token-btn');
    var copyTokenBtn         = document.getElementById('copy-download-token-btn');
    var currentDownloadToken = '';
    var isDownloadTokenVisible = false;
    var lastPushNotificationVersion = null;
    var lastRemoteNotificationVersion = null;
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

    function tokenPreview(token) {
        if (!token) return 'Token configured';
        if (token.length <= 14) return token;
        return token.substring(0, 8) + '...' + token.substring(token.length - 6);
    }

    function setDownloadTokenVisibility(visible) {
        isDownloadTokenVisible = !!visible;

        if (showTokenBtn) {
            showTokenBtn.textContent = isDownloadTokenVisible ? 'Hide' : 'Show';
        }

        if (!tokenValueEl) return;

        if (!currentDownloadToken) {
            tokenValueEl.value = '';
            tokenValueEl.placeholder = 'No token configured';
            return;
        }

        if (isDownloadTokenVisible) {
            tokenValueEl.value = currentDownloadToken;
            tokenValueEl.placeholder = 'Current token loaded';
        } else {
            tokenValueEl.value = '';
            tokenValueEl.placeholder = 'Current token: ' + tokenPreview(currentDownloadToken);
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
        if (releasePackageSelect) renderPackages();
        if (tokenStatusEl) fetchDownloadTokenStatus();
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
    var clientSource   = null;   // active client progress EventSource
    var bulkWatchTimer = null;   // interval timer for multi-client callback tracking
    var bulkWatchState = null;   // state for multi-client callback tracking
    var deployDone     = false;  // guard against onerror firing after normal close
    var watchedClientId = null;
    var lastClientHeartbeatLogAt = 0;
    var runStepNo = 0;

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

    function beginStepProgress(initialLabel) {
        runStepNo = 0;
        if (progressWrap) progressWrap.style.display = '';
        if (progressBar) {
            progressBar.classList.remove('tw-bg-green-500', 'tw-bg-red-500');
            if (!progressBar.classList.contains('tw-bg-amber-500')) {
                progressBar.classList.add('tw-bg-amber-500');
            }
        }
        setProgress(0, initialLabel || 'Starting...');
    }

    function appendStep(message, pct, label) {
        runStepNo++;
        if (logEl) {
            logEl.textContent += 'Step ' + runStepNo + ': ' + message + '\n';
            logWrap.scrollTop = logWrap.scrollHeight;
        }
        if (typeof pct === 'number') {
            setProgress(pct, label || message);
        }
    }

    function stopDeployStream() {
        if (deploySource) { deploySource.close(); deploySource = null; }
    }

    function stopClientStream() {
        if (clientSource) { clientSource.close(); clientSource = null; }
        watchedClientId = null;
        lastClientHeartbeatLogAt = 0;
    }

    function stopBulkClientWatch() {
        if (bulkWatchTimer) {
            clearInterval(bulkWatchTimer);
            bulkWatchTimer = null;
        }
        bulkWatchState = null;
    }

    function startBulkClientWatch(clientTargets, clientBaseline) {
        stopBulkClientWatch();
        stopClientStream();

        var ids = Object.keys(clientTargets || {});
        if (ids.length === 0) {
            return;
        }

        bulkWatchState = {
            ids: ids,
            names: clientTargets,
            baseline: clientBaseline || {},
            lastStatus: {},
            finalStatus: {},
            staleLogged: {},
            startedAt: Date.now()
        };

        if (logWrap) logWrap.style.display = '';
        if (logEl) {
            appendStep('Watching deploy callbacks for ' + ids.length + ' client(s).', 85, 'Waiting for client callbacks...');
            logWrap.scrollTop = logWrap.scrollHeight;
        }

        var timeoutMs = 5 * 60 * 1000;
        var tick = function () {
            if (!bulkWatchState) return;

            fetch('{{ route("superadmin.update.clients") }}', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!bulkWatchState) return;
                var clients = data.clients || [];

                bulkWatchState.ids.forEach(function (id) {
                    if (bulkWatchState.finalStatus[id]) return;

                    var c = clients.find(function (row) { return String(row.id) === String(id); });
                    if (!c) return;

                    var status = c.last_push_status || 'pending';
                    var pushedAt = c.last_pushed_at || '';
                    var baseline = bulkWatchState.baseline[id] || null;
                    var baselineMarker = baseline ? baseline.marker : '';
                    var currentMarker = status + '|' + pushedAt;
                    var freshForThisRun = !baselineMarker || currentMarker !== baselineMarker;

                    if (bulkWatchState.lastStatus[id] !== status) {
                        bulkWatchState.lastStatus[id] = status;
                        if (logEl) {
                            logEl.textContent += '• ' + (bulkWatchState.names[id] || ('Client #' + id)) + ': ' + status + '\n';
                            logWrap.scrollTop = logWrap.scrollHeight;
                        }
                    }

                    if ((status === 'success' || status === 'failed') && freshForThisRun) {
                        bulkWatchState.finalStatus[id] = status;
                        setClientLiveStage(id, null);
                        clearClientRunBaseline(id);
                    } else if ((status === 'success' || status === 'failed') && !freshForThisRun && !bulkWatchState.staleLogged[id]) {
                        bulkWatchState.staleLogged[id] = true;
                        if (logEl) {
                            logEl.textContent += '• ' + (bulkWatchState.names[id] || ('Client #' + id)) + ': waiting for current-run callback (ignoring stale status).\n';
                            logWrap.scrollTop = logWrap.scrollHeight;
                        }
                    }
                });

                if (typeof renderClients === 'function') renderClients();

                var doneCount = Object.keys(bulkWatchState.finalStatus).length;
                if (doneCount >= bulkWatchState.ids.length) {
                    var failed = Object.keys(bulkWatchState.finalStatus).filter(function (id) {
                        return bulkWatchState.finalStatus[id] === 'failed';
                    }).length;
                    var success = bulkWatchState.ids.length - failed;
                    var elapsedSeconds = Math.max(1, Math.round((Date.now() - bulkWatchState.startedAt) / 1000));

                    stopBulkClientWatch();
                    appendStep('Client callbacks completed in ' + elapsedSeconds + 's: ' + success + ' success, ' + failed + ' failed.', 100, 'Callbacks complete');
                    showRunResult(
                        failed === 0,
                        failed === 0
                            ? ('All clients completed deployment successfully (' + success + '/' + (success + failed) + ').')
                            : ('Deployment callbacks finished: ' + success + ' success, ' + failed + ' failed.')
                    );
                }

                if (bulkWatchState && (Date.now() - bulkWatchState.startedAt) > timeoutMs) {
                    stopBulkClientWatch();
                    appendStep('Callback watch timed out. Some clients may still finish in background.', 95, 'Callback watch timed out');
                    showRunResult(false, 'Timed out waiting for client deploy callbacks. Some clients may still be running in background.');
                }
            })
            .catch(function () {
                if (bulkWatchState && (Date.now() - bulkWatchState.startedAt) > timeoutMs) {
                    stopBulkClientWatch();
                    appendStep('Status polling timed out while checking client callbacks.', 95, 'Status polling timed out');
                    showRunResult(false, 'Timed out while checking client statuses.');
                }
            });
        };

        tick();
        bulkWatchTimer = setInterval(tick, 3000);
    }

    function captureClientBaseline(onReady) {
        fetch('{{ route("superadmin.update.clients") }}', { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var baseline = {};
            (data.clients || []).forEach(function (c) {
                var id = String(c.id);
                var status = c.last_push_status || '';
                var pushedAt = c.last_pushed_at || '';
                baseline[id] = {
                    status: status,
                    pushedAt: pushedAt,
                    marker: status + '|' + pushedAt,
                };
                setClientRunBaseline(id, baseline[id].marker);
            });
            onReady(baseline);
        })
        .catch(function () {
            onReady({});
        });
    }

    function findBulkWatchTargetsFromServer(onReady) {
        fetch('{{ route("superadmin.update.clients") }}', { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var clients = data.clients || [];
            var targets = {};

            clients.forEach(function (c) {
                var status = c.last_push_status || '';
                if (status === 'pending' || status === 'success' || status === 'failed') {
                    targets[String(c.id)] = c.name || ('Client #' + c.id);
                }
            });

            onReady(targets);
        })
        .catch(function () {
            onReady({});
        });
    }

    function startClientProgressStream(clientId, clientName) {
        stopClientStream();
        watchedClientId = String(clientId);
        lastClientHeartbeatLogAt = 0;

        if (checkResult) checkResult.style.display = 'none';
        if (preRunEl) preRunEl.style.display = 'none';
        if (logWrap) logWrap.style.display = '';
        if (resultEl) resultEl.style.display = 'none';
        if (logEl) logEl.textContent += '\nWatching ' + clientName + ' deployment progress...\n';

        var url = '{{ route("superadmin.update.clients.progress", ["id" => "__ID__"]) }}'.replace('__ID__', clientId);
        clientSource = new EventSource(url);

        clientSource.addEventListener('progress', function (e) {
            var d = JSON.parse(e.data);
            if (logEl) {
                logEl.textContent += '• ' + (d.message || ('Status: ' + (d.status || 'pending'))) + '\n';
                logWrap.scrollTop = logWrap.scrollHeight;
            }
            renderClients();
        });

        clientSource.addEventListener('heartbeat', function (e) {
            var d = JSON.parse(e.data);
            var nowTs = Date.now();
            if (logEl && (nowTs - lastClientHeartbeatLogAt) >= 8000) {
                logEl.textContent += '• ' + (d.message || 'Waiting...') + '\n';
                logWrap.scrollTop = logWrap.scrollHeight;
                lastClientHeartbeatLogAt = nowTs;
            }
        });

        clientSource.addEventListener('done', function (e) {
            var d = JSON.parse(e.data);
            stopClientStream();
            renderClients();
            showRunResult(!!d.success, d.message || ('Finished with status: ' + (d.status || 'unknown')));
        });

        clientSource.onerror = function () {
            stopClientStream();
            if (logEl) {
                logEl.textContent += '• Live client status stream disconnected.\n';
                logWrap.scrollTop = logWrap.scrollHeight;
            }
        };
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
        stopClientStream();
        stopBulkClientWatch();
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
        function startBuildAndPushAllClients() {
            buildPkgBtn.disabled = true;
            if (buildPkgText) buildPkgText.textContent = 'Building…';
            if (buildPkgSpin) buildPkgSpin.style.display = '';
            if (checkResult) checkResult.style.display = 'none';
            if (preRunEl)    preRunEl.style.display = 'none';
            if (logWrap)     logWrap.style.display = '';
            if (logEl)       logEl.textContent = '';
            beginStepProgress('Preparing build...');
            appendStep('Build package started on central server.', 10, 'Building package...');
            captureClientBaseline(function (clientBaseline) {

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
                appendStep('Package built: v' + d.version + ' (' + d.size_kb + ' KB).', 35, 'Package built');
                if (logEl) {
                    logEl.textContent += 'Package v' + d.version + ' ready (' + d.size_kb + ' KB).\nPushing to clients…\n';
                    logWrap.scrollTop = logWrap.scrollHeight;
                }
                if (typeof renderPackages === 'function') renderPackages();
                if (buildPkgText) buildPkgText.textContent = 'Pushing…';
                appendStep('Dispatching push triggers to active clients.', 50, 'Dispatching push triggers...');
                var pushedClients = {};
                var pushResultsReceived = 0;
                var watchStarted = false;

                // Phase 2: push to all clients via SSE
                deployDone = false;
                deploySource = new EventSource('{{ route("superadmin.update.push-all") }}');

                deploySource.addEventListener('progress', function (e) {
                    var d = JSON.parse(e.data);
                    if (logEl && d.message) {
                        logEl.textContent += d.message + '\n';
                        logWrap.scrollTop = logWrap.scrollHeight;
                    }
                });

                deploySource.addEventListener('clientStart', function (e) {
                    var d = JSON.parse(e.data);
                    setClientPushInFlight(d.client_id, true);
                    setClientLiveStage(d.client_id, 'pushing');
                    if (clientBaseline && clientBaseline[String(d.client_id)]) {
                        setClientRunBaseline(d.client_id, clientBaseline[String(d.client_id)].marker || '');
                    } else {
                        primeClientRunBaseline(d.client_id);
                    }
                    if (logEl) { logEl.textContent += 'Pushing to ' + d.name + '…\n'; logWrap.scrollTop = logWrap.scrollHeight; }
                    if (typeof renderClients === 'function') renderClients();
                });
                deploySource.addEventListener('clientResult', function (e) {
                    var d = JSON.parse(e.data);
                    if (logEl) {
                        logEl.textContent += '  ' + (d.success ? '✓' : '✗') + ' ' + d.name + ': ' + d.message + '\n';
                        logWrap.scrollTop = logWrap.scrollHeight;
                    }
                    pushResultsReceived++;
                    setClientPushInFlight(d.client_id, false);
                    if (d.success) {
                        pushedClients[String(d.client_id)] = d.name || ('Client #' + d.client_id);
                        setClientLiveStage(d.client_id, 'deploying');
                    } else {
                        setClientLiveStage(d.client_id, null);
                        clearClientRunBaseline(d.client_id);
                    }
                    if (typeof renderClients === 'function') renderClients();
                });
                deploySource.addEventListener('done', function (e) {
                    deployDone = true;
                    stopDeployStream();
                    var d = JSON.parse(e.data);
                    var succeeded = Number(d.succeeded || 0);
                    var failed = Number(d.failed || 0);
                    appendStep('Push dispatch finished: ' + succeeded + '/' + d.total + ' accepted, ' + failed + ' failed.', 70, 'Push dispatch complete');

                    if (d.total === 0) {
                        showRunResult(true, 'Package built successfully. ' + (d.message || 'No active clients registered.'));
                    } else if (succeeded > 0 && failed > 0) {
                        showRunResult(true, 'Package built and pushed to ' + succeeded + '/' + d.total + ' client(s). Some clients failed; check log above.');
                        if (!watchStarted) {
                            watchStarted = true;
                            startBulkClientWatch(pushedClients, clientBaseline);
                        }
                    } else if (! d.success) {
                        showRunResult(false, 'Package built, but ' + failed + ' client(s) failed. Check log above.');
                    } else {
                        showRunResult(true, 'Package built and pushed to ' + succeeded + '/' + d.total + ' client(s). Tracking deploy callbacks...');
                        if (!watchStarted) {
                            watchStarted = true;
                            startBulkClientWatch(pushedClients, clientBaseline);
                        }
                    }
                    resetBuildBtn();
                });
                deploySource.onerror = function () {
                    if (deployDone) return;

                    // Browsers can fire onerror when server intentionally closes SSE after done-like dispatch.
                    if (deploySource && deploySource.readyState === EventSource.CLOSED && pushResultsReceived > 0) {
                        deployDone = true;
                        stopDeployStream();
                        appendStep('Push stream closed after dispatch; continuing with callback tracking.', 75, 'Tracking callbacks...');
                        showRunResult(true, 'Package built and push dispatch completed. Tracking deploy callbacks...');
                        if (!watchStarted) {
                            watchStarted = true;
                            startBulkClientWatch(pushedClients, clientBaseline);
                        }
                        resetBuildBtn();
                        return;
                    }

                    stopDeployStream();
                    if (typeof renderClients === 'function') renderClients();
                    if (!watchStarted) {
                        watchStarted = true;
                        findBulkWatchTargetsFromServer(function (derivedTargets) {
                            var targets = Object.keys(pushedClients).length > 0 ? pushedClients : derivedTargets;
                            if (Object.keys(targets).length > 0) {
                                startBulkClientWatch(targets, clientBaseline);
                            }
                        });
                    }
                    appendStep('Live stream interrupted; continuing with server-side status tracking.', 75, 'Tracking callbacks...');
                    showRunResult(true, 'Package built and push request sent. Live stream was interrupted; tracking client statuses below.');
                    resetBuildBtn();
                };
            });

            deploySource.onerror = function () {
                stopDeployStream();
                showRunResult(false, 'Build stream lost. Check server logs.');
                resetBuildBtn();
            };
            });
        }

        buildPkgBtn.addEventListener('click', function () {
            if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
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
                    if (!result.isConfirmed) return;
                    startBuildAndPushAllClients();
                });
                return;
            }

            if (typeof swal === 'function') {
                swal({
                    title: 'Build & Push v{{ config("author.app_version") }}?',
                    text: 'Package the current codebase and push it to all registered client servers.',
                    icon: 'warning',
                    buttons: true,
                    dangerMode: false,
                }).then(function (confirmed) {
                    if (!confirmed) return;
                    startBuildAndPushAllClients();
                });
                return;
            }

            if (!window.confirm('Build and push v{{ config("author.app_version") }} to all registered client servers?')) return;
            startBuildAndPushAllClients();
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
            var startPushAll = function () {
                pushAllBtn.disabled = true;
                if (cancelBtn)  cancelBtn.disabled = true;
                if (closeModal) closeModal.disabled = true;
                if (checkResult) checkResult.style.display = 'none';
                if (preRunEl)   preRunEl.style.display = 'none';
                if (logWrap)    logWrap.style.display = '';
                if (logEl)      logEl.textContent = 'Pushing to clients…\n';
                beginStepProgress('Preparing push...');
                appendStep('Push trigger process started on central server.', 15, 'Starting push...');
                captureClientBaseline(function (clientBaseline) {
                var pushedClients = {};
                var pushResultsReceived = 0;
                var watchStarted = false;
                deployDone = false;
                stopDeployStream();

                deploySource = new EventSource('{{ route("superadmin.update.push-all") }}');

                deploySource.addEventListener('progress', function (e) {
                    var d = JSON.parse(e.data);
                    if (logEl && d.message) {
                        logEl.textContent += d.message + '\n';
                        logWrap.scrollTop = logWrap.scrollHeight;
                    }
                });

                deploySource.addEventListener('clientStart', function (e) {
                    var d = JSON.parse(e.data);
                    setClientPushInFlight(d.client_id, true);
                    setClientLiveStage(d.client_id, 'pushing');
                    if (clientBaseline && clientBaseline[String(d.client_id)]) {
                        setClientRunBaseline(d.client_id, clientBaseline[String(d.client_id)].marker || '');
                    } else {
                        primeClientRunBaseline(d.client_id);
                    }
                    if (logEl) { logEl.textContent += 'Pushing to ' + d.name + '…\n'; logWrap.scrollTop = logWrap.scrollHeight; }
                    if (typeof renderClients === 'function') renderClients();
                });

                deploySource.addEventListener('clientResult', function (e) {
                    var d = JSON.parse(e.data);
                    if (logEl) {
                        logEl.textContent += '  ' + (d.success ? '✓' : '✗') + ' ' + d.name + ': ' + d.message + '\n';
                        logWrap.scrollTop = logWrap.scrollHeight;
                    }
                    pushResultsReceived++;
                    setClientPushInFlight(d.client_id, false);
                    if (d.success) {
                        pushedClients[String(d.client_id)] = d.name || ('Client #' + d.client_id);
                        setClientLiveStage(d.client_id, 'deploying');
                    } else {
                        setClientLiveStage(d.client_id, null);
                        clearClientRunBaseline(d.client_id);
                    }
                    renderClients(); // refresh the clients list
                });

                deploySource.addEventListener('done', function (e) {
                    deployDone = true;
                    stopDeployStream();
                    var d = JSON.parse(e.data);
                    var succeeded = Number(d.succeeded || 0);
                    var failed = Number(d.failed || 0);
                    appendStep('Push dispatch finished: ' + succeeded + '/' + d.total + ' accepted, ' + failed + ' failed.', 70, 'Push dispatch complete');

                    if (d.total === 0) {
                        showRunResult(true, d.message || 'No active clients registered.');
                    } else if (succeeded > 0) {
                        showRunResult(true, 'Pushed to ' + succeeded + '/' + d.total + ' clients. Tracking deploy callbacks...');
                        if (!watchStarted) {
                            watchStarted = true;
                            startBulkClientWatch(pushedClients, clientBaseline);
                        }
                    } else if (d.success) {
                        showRunResult(true, 'Pushed to ' + succeeded + '/' + d.total + ' clients.');
                    } else {
                        showRunResult(false, failed + ' client(s) failed. Check the log above.');
                    }
                    resetButtons();
                });

                deploySource.onerror = function () {
                    if (deployDone) return;

                    // Some browsers fire onerror when the server intentionally closes the stream.
                    if (deploySource && deploySource.readyState === EventSource.CLOSED && pushResultsReceived > 0) {
                        deployDone = true;
                        stopDeployStream();
                        appendStep('Push stream closed after dispatch; continuing with callback tracking.', 75, 'Tracking callbacks...');
                        showRunResult(true, 'Push request sent. Stream closed after dispatch. Tracking deploy callbacks...');
                        if (!watchStarted) {
                            watchStarted = true;
                            startBulkClientWatch(pushedClients, clientBaseline);
                        }
                        resetButtons();
                        return;
                    }

                    stopDeployStream();
                    if (!watchStarted) {
                        watchStarted = true;
                        findBulkWatchTargetsFromServer(function (derivedTargets) {
                            var targets = Object.keys(pushedClients).length > 0 ? pushedClients : derivedTargets;
                            if (Object.keys(targets).length > 0) {
                                startBulkClientWatch(targets, clientBaseline);
                            }
                        });
                    }
                    appendStep('Live stream interrupted; continuing with server-side status tracking.', 75, 'Tracking callbacks...');
                    showRunResult(true, 'Push request sent. Live stream was interrupted; tracking client statuses below.');
                    resetButtons();
                };
                });
            };

            if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                Swal.fire({
                    title: 'Send the update trigger to all active clients?',
                    text: 'Every active client server will be notified to pull the latest update.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, send trigger',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#4f46e5',
                    cancelButtonColor: '#6b7280',
                    reverseButtons: true,
                }).then(function (result) {
                    if (!result.isConfirmed) return;
                    startPushAll();
                });
                return;
            }

            if (typeof swal === 'function') {
                swal({
                    title: 'Send the update trigger to all active clients?',
                    text: 'Every active client server will be notified to pull the latest update.',
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(function (confirmed) {
                    if (!confirmed) return;
                    startPushAll();
                });
                return;
            }

            if (!confirm('Send the update trigger to all active clients?')) return;
            startPushAll();
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
    var clientPushInFlight = {};
    var clientLiveStage    = {};
    var clientRunBaseline  = {};
    var lastClientSnapshot = {};

    if (addClientBtn)       addClientBtn.addEventListener('click', function () { addClientForm && (addClientForm.style.display = 'flex'); });
    if (cancelAddClientBtn) cancelAddClientBtn.addEventListener('click', function () { addClientForm && (addClientForm.style.display = 'none'); });
    if (closeSecretBox)     closeSecretBox.addEventListener('click', function () { clientSecretBox && (clientSecretBox.style.display = 'none'); });

    function setClientPushInFlight(clientId, inFlight) {
        if (inFlight) {
            clientPushInFlight[String(clientId)] = true;
        } else {
            delete clientPushInFlight[String(clientId)];
        }
    }

    function setClientLiveStage(clientId, stage) {
        var key = String(clientId);
        if (!stage) {
            delete clientLiveStage[key];
            return;
        }
        clientLiveStage[key] = String(stage);
    }

    function setClientRunBaseline(clientId, marker) {
        clientRunBaseline[String(clientId)] = { marker: String(marker || '') };
    }

    function clearClientRunBaseline(clientId) {
        delete clientRunBaseline[String(clientId)];
    }

    function primeClientRunBaseline(clientId) {
        var key = String(clientId);
        if (clientRunBaseline[key]) return;
        var snap = lastClientSnapshot[key];
        if (!snap) return;
        setClientRunBaseline(key, String(snap.status || '') + '|' + String(snap.pushedAt || ''));
    }

    function statusBadgeForClient(c) {
        var key = String(c.id);
        var liveStage = clientLiveStage[key] || '';
        if (liveStage === 'pushing') {
            return '<span class="tw-client-status tw-font-semibold tw-text-sky-600">pushing...</span>';
        }
        if (liveStage === 'deploying') {
            return '<span class="tw-client-status tw-font-semibold tw-text-amber-600">deploying...</span>';
        }

        var status = c.last_push_status || '';
        if (status === 'pending') {
            return '<span class="tw-client-status tw-font-semibold tw-text-amber-600">deploying...</span>';
        }
        if (status) {
            var klass = status === 'success' ? 'tw-text-green-600' : (status === 'failed' ? 'tw-text-red-600' : 'tw-text-amber-600');
            return '<span class="tw-client-status tw-font-semibold ' + klass + '">' + status + '</span>';
        }

        return '<span class="tw-client-status tw-text-gray-400">never pushed</span>';
    }

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

    if (refreshPackagesBtn) {
        refreshPackagesBtn.addEventListener('click', function () {
            renderPackages();
        });
    }

    if (deletePackageBtn) {
        deletePackageBtn.addEventListener('click', function () {
            if (!releasePackageSelect) return;
            var filename = releasePackageSelect.value;
            if (!filename) {
                showToast('warn', 'Select a package to delete');
                return;
            }

            var performDelete = function () {
                deletePackageBtn.disabled = true;
                fetch('{{ route("superadmin.update.packages.destroy") }}', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ filename: filename }),
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    showToast(data.success ? 'success' : 'error', data.message || (data.success ? 'Package deleted.' : 'Delete failed.'));
                    renderPackages();
                })
                .catch(function () {
                    showToast('error', 'Delete failed', 'Request could not be completed.');
                })
                .finally(function () {
                    deletePackageBtn.disabled = false;
                });
            };

            if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                Swal.fire({
                    title: 'Delete stored release package?',
                    text: filename,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    reverseButtons: true,
                }).then(function (result) {
                    if (!result.isConfirmed) return;
                    performDelete();
                });
                return;
            }

            if (typeof swal === 'function') {
                swal({
                    title: 'Delete stored release package?',
                    text: filename,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(function (confirmed) {
                    if (!confirmed) return;
                    performDelete();
                });
                return;
            }

            if (!confirm('Delete stored release package ' + filename + '?')) return;
            performDelete();
        });
    }

    if (regenTokenBtn) {
        regenTokenBtn.addEventListener('click', function () {
            var proceed = function () {
                regenTokenBtn.disabled = true;
                fetch('{{ route("superadmin.update.download-token.regenerate") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.success) {
                        showToast('error', data.message || 'Token generation failed.');
                        return;
                    }
                    currentDownloadToken = data.token || '';
                    setDownloadTokenVisibility(true);
                    if (tokenStatusEl) tokenStatusEl.textContent = data.message || 'Managed token active. Copy and paste this into each client .env as UPDATE_AUTH_TOKEN.';
                    showToast('success', data.message || 'Token regenerated.');
                })
                .catch(function () {
                    showToast('error', 'Token generation failed', 'Request could not be completed.');
                })
                .finally(function () {
                    regenTokenBtn.disabled = false;
                });
            };

            if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                Swal.fire({
                    title: 'Regenerate download token?',
                    text: 'Existing client UPDATE_AUTH_TOKEN values will stop working until updated.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, regenerate',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#b45309',
                    cancelButtonColor: '#6b7280',
                    reverseButtons: true,
                }).then(function (result) {
                    if (!result.isConfirmed) return;
                    proceed();
                });
                return;
            }

            if (typeof swal === 'function') {
                swal({
                    title: 'Regenerate download token?',
                    text: 'Existing client UPDATE_AUTH_TOKEN values will stop working until updated.',
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(function (confirmed) {
                    if (!confirmed) return;
                    proceed();
                });
                return;
            }

            if (!confirm('Generate a new client download token? Existing client UPDATE_AUTH_TOKEN values will stop working until updated.')) return;
            proceed();
        });
    }

    if (copyTokenBtn) {
        copyTokenBtn.addEventListener('click', function () {
            var token = tokenValueEl ? tokenValueEl.value.trim() : '';
            if (!token) {
                showToast('warn', 'No token to copy', 'Generate a token first.');
                return;
            }

            var originalLabel = copyTokenBtn.textContent;
            navigator.clipboard.writeText(token)
                .then(function () {
                    showToast('success', 'Token copied');
                    copyTokenBtn.textContent = 'Copied';
                    copyTokenBtn.disabled = true;
                    setTimeout(function () {
                        copyTokenBtn.textContent = originalLabel;
                        copyTokenBtn.disabled = false;
                    }, 1500);
                })
                .catch(function () { showToast('error', 'Copy failed', 'Please copy manually.'); });
        });
    }

    if (showTokenBtn) {
        showTokenBtn.addEventListener('click', function () {
            if (!tokenValueEl) return;
            if (!currentDownloadToken) {
                showToast('warn', 'No token to show', 'Generate or configure a token first.');
                return;
            }
            setDownloadTokenVisibility(!isDownloadTokenVisible);
            showToast('success', isDownloadTokenVisible ? 'Current token revealed.' : 'Current token hidden.');
        });
    }

    function fetchDownloadTokenStatus() {
        fetch('{{ route("superadmin.update.download-token.status") }}', { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.success) {
                if (tokenStatusEl) tokenStatusEl.textContent = 'Could not load token status.';
                return;
            }

            if (tokenStatusEl) tokenStatusEl.textContent = data.message || 'Token status loaded.';
            if (!tokenValueEl) return;

            if (!data.configured) {
                currentDownloadToken = '';
                setDownloadTokenVisibility(false);
                return;
            }

            currentDownloadToken = data.token || '';
            setDownloadTokenVisibility(false);
        })
        .catch(function () {
            if (tokenStatusEl) tokenStatusEl.textContent = 'Could not load token status.';
        });
    }

    function renderPackages() {
        if (!releasePackageSelect) return;

        releasePackageSelect.innerHTML = '<option value="">Loading packages...</option>';

        fetch('{{ route("superadmin.update.packages") }}', { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var packages = data.packages || [];
            if (!data.success) {
                releasePackageSelect.innerHTML = '<option value="">Could not load packages</option>';
                return;
            }
            if (packages.length === 0) {
                releasePackageSelect.innerHTML = '<option value="">No stored packages</option>';
                return;
            }

            releasePackageSelect.innerHTML = packages.map(function (p) {
                var label = p.filename + ' (' + p.size_kb + ' KB' + (p.is_current ? ', active' : '') + ')';
                return '<option value="' + escHtml(p.filename) + '">' + escHtml(label) + '</option>';
            }).join('');

            var current = packages.find(function (p) { return !!p.is_current; });
            if (current) {
                releasePackageSelect.value = current.filename;
            }
        })
        .catch(function () {
            releasePackageSelect.innerHTML = '<option value="">Could not load packages</option>';
        });
    }

    function renderClients() {
        if (! clientsList) return;
        fetch('{{ route("superadmin.update.clients") }}', { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var clients = data.clients || [];

            // If we are watching one client live and it has finished, stop waiting immediately.
            if (watchedClientId !== null) {
                var watched = clients.find(function (c) { return String(c.id) === watchedClientId; });
                if (watched && (watched.last_push_status === 'success' || watched.last_push_status === 'failed')) {
                    stopClientStream();
                    setClientLiveStage(watched.id, null);
                    clearClientRunBaseline(watched.id);
                    showRunResult(
                        watched.last_push_status === 'success',
                        watched.last_push_status === 'success'
                            ? (watched.name + ' finished deployment successfully.')
                            : (watched.name + ' deployment failed. Check client logs.')
                    );
                }
            }

            if (clients.length === 0) {
                clientsList.innerHTML = '<p class="tw-text-gray-400 tw-italic">No clients registered yet.</p>';
                return;
            }
            clients.forEach(function (c) {
                var key = String(c.id);
                lastClientSnapshot[key] = {
                    status: c.last_push_status || '',
                    pushedAt: c.last_pushed_at || '',
                };

                var baseline = clientRunBaseline[key];
                if (!baseline) return;

                var status = c.last_push_status || '';
                var marker = status + '|' + (c.last_pushed_at || '');
                var freshFinal = (status === 'success' || status === 'failed') && marker !== String(baseline.marker || '');
                if (freshFinal) {
                    setClientLiveStage(key, null);
                    clearClientRunBaseline(key);
                }
            });

            clientsList.innerHTML = clients.map(function (c) {
                var key = String(c.id);
                var isPushing = !!clientPushInFlight[key];
                var isDeploying = clientLiveStage[key] === 'deploying';
                var isBusy = isPushing || isDeploying;
                var status = statusBadgeForClient(c);
                return '<div class="tw-py-2 tw-border-b tw-border-gray-100">' +
                    '<div class="tw-min-w-0 tw-break-words tw-leading-5">' +
                    '<span class="tw-font-medium">' + escHtml(c.name) + '</span> ' +
                    '<span class="tw-text-gray-500">' + escHtml(c.url) + '</span>' +
                    '<div class="tw-text-xs tw-text-gray-600 tw-mt-0.5">v' + escHtml(c.last_version || '?') + ' &mdash; ' + status + '</div>' +
                    '</div>' +
                    '<div class="tw-flex tw-flex-wrap tw-items-center tw-gap-2 tw-mt-2">' +
                    '<button class="tw-text-xs tw-font-medium tw-text-slate-600 hover:tw-underline tw-client-show-token" data-id="' + c.id + '" data-name="' + escHtml(c.name) + '">Show Token</button>' +
                    '<button class="tw-text-xs tw-font-medium tw-text-amber-600 hover:tw-underline tw-client-rotate-secret" data-id="' + c.id + '" data-name="' + escHtml(c.name) + '">Rotate Secret</button>' +
                    '<button class="tw-text-xs tw-font-medium tw-text-indigo-600 hover:tw-underline tw-client-push" ' + (isBusy ? 'disabled ' : '') + 'data-id="' + c.id + '" data-name="' + escHtml(c.name) + '">' + (isPushing ? 'Pushing...' : (isDeploying ? 'Deploying...' : 'Push')) + '</button>' +
                    '<button class="tw-text-xs tw-font-medium tw-text-red-500 hover:tw-underline tw-client-delete" data-id="' + c.id + '">Remove</button>' +
                    '</div>' +
                    '</div>';
            }).join('');

            clientsList.querySelectorAll('.tw-client-show-token').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (btn.textContent === 'Hide Token') {
                        if (clientSecretValue) clientSecretValue.textContent = '';
                        clientSecretBox && (clientSecretBox.style.display = 'none');
                        btn.textContent = 'Show Token';
                        showToast('success', (btn.dataset.name ? btn.dataset.name + ': ' : '') + 'token hidden.');
                        return;
                    }

                    btn.disabled = true;
                    var id = btn.dataset.id;
                    var url = '{{ route("superadmin.update.clients.token", ["id" => "__ID__"]) }}'.replace('__ID__', id);

                    fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success && data.webhook_secret) {
                            if (clientSecretValue) clientSecretValue.textContent = 'UPDATE_WEBHOOK_SECRET=' + data.webhook_secret;
                            clientSecretBox && (clientSecretBox.style.display = '');
                            btn.textContent = 'Hide Token';
                            showToast('success', (data.client && data.client.name ? data.client.name + ': ' : '') + 'token loaded.');
                        } else {
                            showToast('error', data.message || 'Could not load client token.');
                        }
                    })
                    .catch(function () {
                        showToast('error', 'Could not load client token.');
                    })
                    .finally(function () {
                        btn.disabled = false;
                    });
                });
            });

            clientsList.querySelectorAll('.tw-client-rotate-secret').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var rotateSecret = function () {
                        btn.disabled = true;
                        var id = btn.dataset.id;
                        var url = '{{ route("superadmin.update.clients.rotate-secret", ["id" => "__ID__"]) }}'.replace('__ID__', id);
                        fetch(url, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                        })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (data.success && data.webhook_secret) {
                                if (clientSecretValue) clientSecretValue.textContent = 'UPDATE_WEBHOOK_SECRET=' + data.webhook_secret;
                                clientSecretBox && (clientSecretBox.style.display = '');
                                showToast('success', data.message || 'Webhook secret rotated.');
                            } else {
                                showToast('error', data.message || 'Failed to rotate webhook secret.');
                            }
                            renderClients();
                        })
                        .catch(function () {
                            showToast('error', 'Failed to rotate webhook secret.');
                        })
                        .finally(function () {
                            btn.disabled = false;
                        });
                    };

                    if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                        Swal.fire({
                            title: 'Rotate webhook secret for ' + btn.dataset.name + '?',
                            text: 'The old secret will stop working immediately on push.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, rotate',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#d97706',
                            cancelButtonColor: '#6b7280',
                            reverseButtons: true,
                        }).then(function (result) {
                            if (!result.isConfirmed) return;
                            rotateSecret();
                        });
                        return;
                    }

                    if (!confirm('Rotate webhook secret for ' + btn.dataset.name + '?')) return;
                    rotateSecret();
                });
            });

            clientsList.querySelectorAll('.tw-client-push').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var pushClient = function () {
                        var id = btn.dataset.id;
                        primeClientRunBaseline(id);
                        setClientPushInFlight(id, true);
                        setClientLiveStage(id, 'pushing');
                        renderClients();

                        if (logWrap) logWrap.style.display = '';
                        if (logEl) {
                            logEl.textContent = 'Sending trigger to ' + (btn.dataset.name || 'client') + '...\n';
                            logWrap.scrollTop = logWrap.scrollHeight;
                        }

                        var url = '{{ route("superadmin.update.clients.push", ["id" => "__ID__"]) }}'.replace('__ID__', id);
                        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            showToast(data.success ? 'success' : 'error', data.message || (data.success ? 'Pushed' : 'Failed'));
                            setClientPushInFlight(id, false);
                            if (data.success) {
                                setClientLiveStage(id, 'deploying');
                            } else {
                                setClientLiveStage(id, null);
                                clearClientRunBaseline(id);
                            }
                            renderClients();
                            if (data.success) {
                                startClientProgressStream(id, btn.dataset.name || ('Client #' + id));
                            }
                        })
                        .catch(function () {
                            setClientPushInFlight(id, false);
                            setClientLiveStage(id, null);
                            clearClientRunBaseline(id);
                            renderClients();
                            showToast('error', 'Push failed', 'Request could not be completed.');
                        });
                    };

                    if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                        Swal.fire({
                            title: 'Push update trigger to ' + btn.dataset.name + '?',
                            text: 'This will notify the client server to pull the latest update.',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, push',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#4f46e5',
                            cancelButtonColor: '#6b7280',
                            reverseButtons: true,
                        }).then(function (result) {
                            if (!result.isConfirmed) return;
                            pushClient();
                        });
                        return;
                    }

                    if (typeof swal === 'function') {
                        swal({
                            title: 'Push update trigger to ' + btn.dataset.name + '?',
                            text: 'This will notify the client server to pull the latest update.',
                            icon: 'warning',
                            buttons: true,
                            dangerMode: false,
                        }).then(function (confirmed) {
                            if (!confirmed) return;
                            pushClient();
                        });
                        return;
                    }

                    if (! confirm('Push update trigger to ' + btn.dataset.name + '?')) return;
                    pushClient();
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

    // Keep client statuses fresh while the modal is open so pending can move to success/failed.
    setInterval(function () {
        if (!modal || modal.style.display !== 'flex') return;
        if (!clientsList) return;
        renderClients();
    }, 5000);

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Background poll every 30s for pushed update notifications ─────────
    setInterval(function () {
        fetch('{{ route("superadmin.update.status") }}', { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.push_pending && data.push_version) {
                if (lastPushNotificationVersion !== data.push_version) {
                    showToast('warning', 'Update received from source (v' + data.push_version + ')', 'Client will pull/deploy in the background. Open System Updates to monitor progress.');
                    lastPushNotificationVersion = data.push_version;
                }
                return;
            }

            if (data && data.remote_pending && data.remote_version) {
                if (lastRemoteNotificationVersion !== data.remote_version) {
                    showToast('warning', 'Update available on source (v' + data.remote_version + ')', 'A newer version is available at the source server. Open System Updates to pull/deploy.');
                    lastRemoteNotificationVersion = data.remote_version;
                }
            } else {
                lastRemoteNotificationVersion = null;
            }

            if (data && !data.push_pending) {
                lastPushNotificationVersion = null;
            }
        })
        .catch(function () {});
    }, 30 * 1000);
})();
</script>
@endpush
{{-- NOTE: Uses @push('scripts') — must match @stack('scripts') in layouts/partials/javascripts.blade.php --}}
