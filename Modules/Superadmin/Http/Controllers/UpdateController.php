<?php

namespace Modules\Superadmin\Http\Controllers;

use App\System;
use Composer\Semver\Comparator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Superadmin\Entities\UpdateClient;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UpdateController extends BaseController
{
    /**
     * Keep controller auth logic consistent with update-banner visibility.
     */
    private function canManageUpdates(): bool
    {
        $user = auth()->user();
        return $user && ($user->role === 'admin' || $user->can('superadmin'));
    }

    /**
     * Public endpoint — returns the current code version.
     * Client installations poll this URL to detect new releases.
     * No authentication required.
     */
    public function versionInfo(): JsonResponse
    {
        // Read directly from the config file so version is always current,
        // even when config:cache has not been re-run after a version bump.
        $author = require base_path('config/author.php');

        return response()->json([
            'version'     => $author['app_version'] ?? '0',
            'released_at' => $author['released_at'] ?? date('Y-m-d'),
        ]);
    }

    /**
     * Return update status as JSON.
     * Available to all authenticated users.
     */
    public function status(): JsonResponse
    {
        $codeVersion      = config('author.app_version', '0');
        $installedVersion = System::getProperty('app_version') ?? '';
        $pushPending      = Cache::get('pending_pull_update');
        $pushPendingVersion = is_array($pushPending) ? (string) ($pushPending['version'] ?? '') : '';
        $hasPushPending   = $pushPendingVersion !== '';

        // Only trust remote_available_version when an update_check_url is configured;
        // otherwise the value may be stale from a prior environment.
        $checkUrl    = config('author.update_check_url', '');
        $remoteVersion = '';
        if ($checkUrl !== '') {
            // Prefer live source check so clients can detect updates even before push is triggered.
            $remoteVersion = $this->fetchLiveRemoteVersion($checkUrl)
                ?: (System::getProperty('remote_available_version') ?? '');
        }

        // Local pending: code files are ahead of the DB (e.g. after git pull but before deploy)
        $localPending = $installedVersion === ''
            || Comparator::greaterThan($codeVersion, $installedVersion);

        // Remote pending: the update server published a version the client hasn't installed yet
        $remotePending = $remoteVersion !== ''
            && Comparator::greaterThan($remoteVersion, $installedVersion);

        $pending = $localPending || $remotePending || $hasPushPending;

        // Surface the most relevant "new" version to the UI
        $displayVersion = $hasPushPending
            ? $pushPendingVersion
            : (($remotePending && ! $localPending) ? $remoteVersion : $codeVersion);

        return response()->json([
            'pending'           => $pending,
            'local_pending'     => $localPending,
            'remote_pending'    => $remotePending,
            'push_pending'      => $hasPushPending,
            'new_version'       => $displayVersion,
            'installed_version' => $installedVersion ?: 'unknown',
            'remote_version'    => $remoteVersion ?: null,
            'push_version'      => $pushPendingVersion !== '' ? $pushPendingVersion : null,
            'push_received_at'  => is_array($pushPending) ? ($pushPending['received_at'] ?? null) : null,
        ]);
    }

    /** Fetch latest source version from UPDATE_CHECK_URL, then cache it in System properties. */
    private function fetchLiveRemoteVersion(string $checkUrl): ?string
    {
        try {
            $response = Http::timeout(5)->get($checkUrl);
            if (! $response->successful()) {
                return null;
            }

            $version = (string) ($response->json('version') ?? '');
            if (! preg_match('/^\d+(\.\d+)*$/', $version)) {
                return null;
            }

            System::updateOrCreate(['key' => 'remote_available_version'], ['value' => $version]);
            return $version;
        } catch (\Throwable $e) {
            Log::warning('Live remote version check failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Dismiss the update banner for 24 hours (per session).
     */
    public function dismiss(Request $request): JsonResponse
    {
        $request->session()->put('update_dismissed_until', now()->addHours(24)->timestamp);

        return response()->json(['dismissed' => true]);
    }

    /**
     * Stream pos:deploy progress as Server-Sent Events (superadmin only).
     *
     * Each deploy step emits an `event: progress` SSE event while it runs,
     * then a `event: stepError` on failure, and a final `event: done`.
     * The session lock is released immediately so other browser tabs
     * are not blocked during the long-running stream.
     */
    public function progress(Request $request): StreamedResponse
    {
        if (! $this->canManageUpdates()) {
            abort(403);
        }

        // Release the session file-lock so other tab requests are not blocked.
        $request->session()->save();

        return response()->stream(function () {
            // Flush any existing output buffers so SSE events are sent immediately.
            while (@ob_end_flush()) {}

            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            $emit = function (string $event, array $payload): void {
                echo "event: {$event}\n";
                echo 'data: ' . json_encode($payload) . "\n\n";
                @ob_flush();
                flush();
            };

            $allOutput = '';
            $success   = true;

            $emit('progress', ['pct' => 5, 'label' => 'Preparing environment…', 'output' => '']);

            $steps = [
                [
                    'label' => 'Running pos:setup…',
                    'pct'   => 14,
                    'run'   => function () {
                        Artisan::call('pos:setup', ['--force' => true]);
                        return Artisan::output();
                    },
                ],
                [
                    'label' => 'Applying migrations…',
                    'pct'   => 28,
                    'run'   => function () {
                        Artisan::call('migrate', ['--force' => true]);
                        return Artisan::output();
                    },
                ],
                [
                    'label' => 'Migrating modules…',
                    'pct'   => 40,
                    'run'   => function () {
                        Artisan::call('module:migrate', ['--force' => true]);
                        return Artisan::output();
                    },
                ],
                [
                    'label' => 'Publishing module assets…',
                    'pct'   => 51,
                    'run'   => function () {
                        Artisan::call('module:publish');
                        return Artisan::output();
                    },
                ],
                [
                    'label' => 'Seeding permissions…',
                    'pct'   => 62,
                    'run'   => function () {
                        Artisan::call('db:seed', ['--class' => 'PermissionsTableSeeder', '--force' => true]);
                        return Artisan::output();
                    },
                ],
                [
                    'label' => 'Installing Passport keys…',
                    'pct'   => 72,
                    'run'   => function () {
                        Artisan::call('passport:install', ['--force' => true]);
                        return Artisan::output();
                    },
                ],
                [
                    'label' => 'Resetting permission cache…',
                    'pct'   => 80,
                    'run'   => function () {
                        Artisan::call('permission:cache-reset');
                        return Artisan::output();
                    },
                ],
                [
                    'label' => 'Caching config & routes…',
                    'pct'   => 90,
                    'run'   => function () {
                        $out = '';
                        try {
                            Artisan::call('config:cache');
                            $out .= Artisan::output();
                        } catch (\Throwable $e) {
                            Artisan::call('config:clear');
                            $out .= Artisan::output();
                        }
                        try {
                            Artisan::call('route:cache');
                            $out .= Artisan::output();
                        } catch (\Throwable $e) {
                            Artisan::call('route:clear');
                            $out .= Artisan::output();
                        }
                        try {
                            Artisan::call('view:cache');
                            $out .= Artisan::output();
                        } catch (\Throwable $e) {
                            Artisan::call('view:clear');
                            $out .= Artisan::output();
                        }
                        return $out;
                    },
                ],
                [
                    'label' => 'Finalising…',
                    'pct'   => 97,
                    'run'   => function () {
                        // Read directly from the source file so we always stamp the version
                        // that was just deployed — not the stale in-memory config value that
                        // was loaded at request bootstrap (which may reflect the old cache).
                        $authorConfig = @include config_path('author.php');
                        $version = (is_array($authorConfig) && isset($authorConfig['app_version']))
                            ? $authorConfig['app_version']
                            : config('author.app_version', '0');

                        System::updateOrCreate(['key' => 'app_version'], ['value' => $version]);
                        return "Version {$version} stamped.\n";
                    },
                ],
            ];

            foreach ($steps as $step) {
                $emit('progress', ['pct' => $step['pct'], 'label' => $step['label'], 'output' => '']);
                try {
                    $output     = ($step['run'])();
                    $allOutput .= $output;
                    $emit('progress', ['pct' => $step['pct'], 'label' => $step['label'] . ' ✓', 'output' => $output]);
                } catch (\Throwable $e) {
                    Log::error('pos:deploy SSE step failed: ' . $step['label'] . ' — ' . $e->getMessage());
                    $allOutput .= $e->getMessage();
                    $emit('stepError', ['pct' => $step['pct'], 'label' => $step['label'] . ' — failed', 'output' => $e->getMessage()]);
                    $success = false;
                    break;
                }
            }

            if ($success) {
                Log::info('pos:deploy completed via SSE. Version: ' . config('author.app_version', '0'));
            } else {
                Log::warning('pos:deploy SSE finished with errors.');
            }

            $emit('done', ['success' => $success, 'output' => $allOutput, 'pct' => $success ? 100 : 97]);
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    /**
     * Run pos:deploy (superadmin only).
     */
    public function run(Request $request): JsonResponse
    {
        if (! $this->canManageUpdates()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            $exitCode = Artisan::call('pos:deploy', ['--force' => true]);
            $output   = Artisan::output();

            if ($exitCode === 0) {
                // Stamp the installed version so the banner disappears
                $codeVersion = config('author.app_version', '0');
                System::updateOrCreate(
                    ['key' => 'app_version'],
                    ['value' => $codeVersion]
                );

                // Clear the dismiss flag so we don't hide a future real update
                $request->session()->forget('update_dismissed_until');

                Log::info("pos:deploy completed successfully via web. Version stamped: {$codeVersion}");
            } else {
                Log::warning("pos:deploy finished with exit code {$exitCode} via web.", ['output' => $output]);
            }

            return response()->json([
                'success' => $exitCode === 0,
                'output'  => $output,
            ]);
        } catch (\Throwable $e) {
            Log::error('pos:deploy web trigger failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'output'  => 'An error occurred. Check server logs for details.',
            ], 500);
        }
    }

    // =========================================================================
    // CENTRAL SERVER — serves release packages to registered clients
    // =========================================================================

    /**
     * Return latest release info (version, download URL, SHA-256).
     * Authenticated by a static bearer token (UPDATE_DOWNLOAD_TOKEN in .env).
     * Called by client servers' pos:pull-update command and pullProgress SSE.
     */
    public function releaseInfo(Request $request): JsonResponse
    {
        if (! $this->authorizeDownloadToken($request)) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $manifest = $this->loadManifest();
        if (! $manifest) {
            return response()->json(['error' => 'No release package available. Run: php artisan pos:package-release'], 404);
        }

        return response()->json([
            'version'      => $manifest['version'],
            'download_url' => route('superadmin.update.package'),
            'sha256'       => $manifest['sha256'] ?? '',
            'packaged_at'  => $manifest['packaged_at'] ?? null,
        ]);
    }

    /**
     * Stream the release zip to an authenticated client server.
     */
    public function downloadPackage(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        if (! $this->authorizeDownloadToken($request)) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $manifest = $this->loadManifest();
        if (! $manifest) {
            return response()->json(['error' => 'No package available.'], 404);
        }

        $zipPath = storage_path('app/releases/' . $manifest['filename']);
        if (! file_exists($zipPath)) {
            return response()->json(['error' => 'Package file not found on disk.'], 404);
        }

        return response()->download($zipPath, $manifest['filename'], ['Content-Type' => 'application/zip']);
    }

    /** List stored release zip packages (superadmin only). */
    public function packages(): JsonResponse
    {
        if (! $this->canManageUpdates()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $manifest = $this->loadManifest();
        $current  = is_array($manifest) ? ($manifest['filename'] ?? null) : null;

        $packages = $this->listReleasePackages();
        $rows = array_map(function (array $pkg) use ($current) {
            return [
                'filename'    => $pkg['filename'],
                'version'     => $pkg['version'],
                'size_kb'     => $pkg['size_kb'],
                'modified_at' => $pkg['modified_at'],
                'is_current'  => $current !== null && $current === $pkg['filename'],
            ];
        }, $packages);

        return response()->json(['success' => true, 'packages' => $rows]);
    }

    /** Return current client download auth token status (superadmin only). */
    public function downloadTokenStatus(): JsonResponse
    {
        if (! $this->canManageUpdates()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $dbToken  = (string) (System::getProperty('update_download_token') ?? '');
        $envToken = (string) env('UPDATE_DOWNLOAD_TOKEN', '');

        if ($envToken !== '') {
            return response()->json([
                'success'    => true,
                'configured' => true,
                'source'     => 'env',
                'token'      => $envToken,
                'preview'    => substr($envToken, 0, 8) . '...' . substr($envToken, -6),
                'message'    => 'Source token is pinned from .env and will not rotate from this panel.',
            ]);
        }

        if ($dbToken !== '') {
            return response()->json([
                'success'    => true,
                'configured' => true,
                'source'     => 'database',
                'token'      => $dbToken,
                'preview'    => substr($dbToken, 0, 8) . '...' . substr($dbToken, -6),
                'message'    => 'Managed token is active.',
            ]);
        }

        return response()->json([
            'success'    => true,
            'configured' => false,
            'source'     => null,
            'token'      => null,
            'preview'    => null,
            'message'    => 'No token configured yet.',
        ]);
    }

    /** Generate or rotate the central download auth token (superadmin only). */
    public function regenerateDownloadToken(): JsonResponse
    {
        if (! $this->canManageUpdates()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $envToken = trim((string) env('UPDATE_DOWNLOAD_TOKEN', ''));
        if ($envToken !== '') {
            return response()->json([
                'success' => true,
                'token'   => $envToken,
                'pinned'  => true,
                'message' => 'Token is pinned from .env. Update UPDATE_DOWNLOAD_TOKEN to change it.',
            ]);
        }

        $token = bin2hex(random_bytes(32)); // 64-char hex token
        System::updateOrCreate(['key' => 'update_download_token'], ['value' => $token]);

        return response()->json([
            'success' => true,
            'token'   => $token,
            'message' => 'Download token regenerated. Update clients with the new UPDATE_AUTH_TOKEN value.',
        ]);
    }

    /** Delete one stored release zip package (superadmin only). */
    public function destroyPackage(Request $request): JsonResponse
    {
        if (! $this->canManageUpdates()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'filename' => ['required', 'string', 'regex:/^v[0-9A-Za-z._-]+\\.zip$/'],
        ]);

        $filename = $validated['filename'];
        $dir = storage_path('app/releases');
        $path = $dir . '/' . $filename;

        if (! is_file($path)) {
            return response()->json(['success' => false, 'message' => 'Package not found.'], 404);
        }

        // Guard against unexpected paths even though filename is validated.
        $realDir = realpath($dir);
        $realPath = realpath($path);
        if ($realDir === false || $realPath === false || strpos($realPath, $realDir . DIRECTORY_SEPARATOR) !== 0) {
            return response()->json(['success' => false, 'message' => 'Invalid package path.'], 400);
        }

        @unlink($realPath);

        $manifest = $this->loadManifest();
        $current = is_array($manifest) ? ($manifest['filename'] ?? null) : null;

        if ($current === $filename) {
            $remaining = $this->listReleasePackages();
            $manifestPath = $dir . '/manifest.json';

            if (empty($remaining)) {
                @unlink($manifestPath);
            } else {
                $next = $remaining[0];
                $nextPath = $dir . '/' . $next['filename'];

                $newManifest = [
                    'version' => $next['version'],
                    'filename' => $next['filename'],
                    'sha256' => hash_file('sha256', $nextPath),
                    'size_kb' => $next['size_kb'],
                    'packaged_at' => date('c', filemtime($nextPath) ?: time()),
                ];

                file_put_contents($manifestPath, json_encode($newManifest, JSON_PRETTY_PRINT));
            }
        }

        return response()->json(['success' => true, 'message' => 'Package deleted successfully.']);
    }

    // =========================================================================
    // CENTRAL SERVER — client registry
    // =========================================================================

    /** List registered client servers. */
    public function clients(): JsonResponse
    {
        if (! $this->canManageUpdates()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $clients = UpdateClient::orderBy('name')
            ->get(['id', 'name', 'url', 'last_version', 'last_push_status', 'last_pushed_at', 'is_active']);

        return response()->json(['clients' => $clients]);
    }

    /** Register a new client server. Returns the one-time webhook secret. */
    public function storeClient(Request $request): JsonResponse
    {
        if (! $this->canManageUpdates()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'url'  => 'required|url|max:255',
        ]);

        $secret = bin2hex(random_bytes(32)); // 64-char hex secret
        $client = UpdateClient::create([
            'name'           => $validated['name'],
            'url'            => rtrim($validated['url'], '/'),
            'webhook_secret' => $secret,
        ]);

        return response()->json([
            'success'        => true,
            'client'         => $client->only(['id', 'name', 'url', 'is_active']),
            'webhook_secret' => $secret, // shown once — store it as UPDATE_WEBHOOK_SECRET on the client
        ], 201);
    }

    /** Remove a registered client. */
    public function destroyClient(int $id): JsonResponse
    {
        if (! $this->canManageUpdates()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        UpdateClient::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    /** Rotate and return a client's webhook secret (shown once). */
    public function rotateClientSecret(int $id): JsonResponse
    {
        if (! $this->canManageUpdates()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $client = UpdateClient::findOrFail($id);
        $secret = bin2hex(random_bytes(32));

        $client->update(['webhook_secret' => $secret]);

        return response()->json([
            'success' => true,
            'message' => 'Webhook secret rotated. Update the client .env value and clear cache.',
            'client' => $client->only(['id', 'name', 'url']),
            'webhook_secret' => $secret,
        ]);
    }

    /** Return a client's current webhook secret for copy/display. */
    public function clientToken(int $id): JsonResponse
    {
        if (! $this->canManageUpdates()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $client = UpdateClient::findOrFail($id);

        return response()->json([
            'success' => true,
            'client' => $client->only(['id', 'name', 'url']),
            'webhook_secret' => (string) $client->webhook_secret,
            'message' => 'Client webhook token loaded.',
        ]);
    }

    /** Build a release package from the current codebase — streams live output as SSE. */
    public function buildPackage(Request $request): StreamedResponse
    {
        if (! $this->canManageUpdates()) {
            abort(403);
        }

        $request->session()->save();

        return response()->stream(function () {
            while (@ob_end_flush()) {}

            $emit = function (string $event, array $payload): void {
                echo "event: {$event}\n";
                echo 'data: ' . json_encode($payload) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
            };

            // PHP_BINARY is empty in web (Apache) context; resolve the binary explicitly.
            $phpBin = PHP_BINARY;
            if (empty($phpBin) || ! is_file($phpBin) || ! is_executable($phpBin)) {
                $phpBin = trim((string) @shell_exec('command -v php8.4 2>/dev/null'))
                    ?: trim((string) @shell_exec('command -v php 2>/dev/null'))
                    ?: '/usr/bin/php8.4';
            }
            $artisan = base_path('artisan');
            $cmd     = escapeshellarg($phpBin) . ' ' . escapeshellarg($artisan) . ' pos:package-release --force --no-ansi 2>&1';

            $emit('progress', ['message' => 'Starting package build…']);

            $handle = popen($cmd, 'r');
            if (! $handle) {
                $emit('done', ['success' => false, 'message' => 'Failed to start build process.']);
                return;
            }

            while (! feof($handle)) {
                $line = fgets($handle);
                if ($line !== false && trim($line) !== '') {
                    $emit('progress', ['message' => trim($line)]);
                }
            }

            $exitCode = pclose($handle);

            if ($exitCode !== 0) {
                $emit('done', ['success' => false, 'message' => 'Build process exited with code ' . $exitCode]);
                return;
            }

            $manifest = $this->loadManifest();
            $emit('done', [
                'success'  => true,
                'version'  => $manifest['version']  ?? '',
                'size_kb'  => $manifest['size_kb']  ?? 0,
                'sha256'   => $manifest['sha256']    ?? '',
            ]);
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /** Push update trigger to a single client. */
    public function pushToClient(Request $request, int $id): JsonResponse
    {
        if (! $this->canManageUpdates()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $client = UpdateClient::findOrFail($id);
        [$ok, $msg] = $this->dispatchPush($client);

        return response()->json(['success' => $ok, 'message' => $msg, 'client_id' => $id]);
    }

    /** Stream live status for one client after push trigger (SSE). */
    public function clientProgress(Request $request, int $id): StreamedResponse
    {
        if (! $this->canManageUpdates()) {
            abort(403);
        }

        $request->session()->save();

        return response()->stream(function () use ($id) {
            while (@ob_end_flush()) {}

            $emit = function (string $event, array $payload): void {
                echo "event: {$event}\n";
                echo 'data: ' . json_encode($payload) . "\n\n";
                @ob_flush();
                flush();
            };

            $client = UpdateClient::find($id);
            if (! $client) {
                $emit('done', ['success' => false, 'status' => 'failed', 'message' => 'Client not found.']);
                return;
            }

            $emit('progress', ['status' => (string) ($client->last_push_status ?? 'pending'), 'message' => "Watching {$client->name} for deploy callback…"]);

            $lastStatus = null;
            $startedAt = time();
            $timeoutSeconds = 300;

            while ((time() - $startedAt) < $timeoutSeconds) {
                $client->refresh();

                $status = (string) ($client->last_push_status ?? 'pending');
                $pushedAt = $client->last_pushed_at ? $client->last_pushed_at->toDateTimeString() : null;

                if ($status !== $lastStatus) {
                    $emit('progress', [
                        'status' => $status,
                        'message' => 'Client status: ' . $status . ($pushedAt ? (' (updated ' . $pushedAt . ')') : ''),
                    ]);
                    $lastStatus = $status;
                } else {
                    $emit('heartbeat', ['status' => $status, 'message' => 'Waiting for client deploy callback…']);
                }

                if (in_array($status, ['success', 'failed'], true)) {
                    $emit('done', [
                        'success' => $status === 'success',
                        'status' => $status,
                        'message' => $status === 'success'
                            ? "{$client->name} finished deployment successfully."
                            : "{$client->name} deployment failed. Check client logs.",
                    ]);
                    return;
                }

                sleep(2);
            }

            $client->refresh();
            $status = (string) ($client->last_push_status ?? 'pending');

            $emit('done', [
                'success' => $status === 'success',
                'status' => $status,
                'message' => $status === 'pending'
                    ? 'Timed out waiting for callback. Client may still be deploying in background.'
                    : "Finished with status: {$status}",
            ]);
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    /** Push update trigger to ALL active clients — streams results as SSE. */
    public function pushAll(Request $request): StreamedResponse
    {
        if (! $this->canManageUpdates()) {
            abort(403);
        }

        $request->session()->save();

        return response()->stream(function () {
            while (@ob_end_flush()) {}

            $emit = function (string $event, array $payload): void {
                echo "event: {$event}\n";
                echo 'data: ' . json_encode($payload) . "\n\n";
                @ob_flush();
                flush();
            };

            $clients = UpdateClient::where('is_active', true)->get();

            if ($clients->isEmpty()) {
                $emit('done', ['success' => true, 'succeeded' => 0, 'failed' => 0, 'total' => 0, 'message' => 'No active clients registered.']);
                return;
            }

            $succeeded = 0;
            $failed    = 0;

            foreach ($clients as $client) {
                $emit('clientStart', ['client_id' => $client->id, 'name' => $client->name]);
                [$ok, $msg] = $this->dispatchPush($client);
                $ok ? $succeeded++ : $failed++;
                $emit('clientResult', [
                    'client_id' => $client->id,
                    'name'      => $client->name,
                    'success'   => $ok,
                    'message'   => $msg,
                ]);
            }

            $emit('done', [
                'success'   => $failed === 0,
                'succeeded' => $succeeded,
                'failed'    => $failed,
                'total'     => $clients->count(),
            ]);
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    // =========================================================================
    // CLIENT SERVER — pulls update from central and applies it
    // =========================================================================

    /**
     * SSE stream: download release from central server then run pos:deploy.
     * Requires UPDATE_SERVER_URL and UPDATE_AUTH_TOKEN in .env.
     */
    public function pullProgress(Request $request): StreamedResponse
    {
        if (! $this->canManageUpdates()) {
            abort(403);
        }

        $request->session()->save();

        return response()->stream(function () {
            while (@ob_end_flush()) {}
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            $emit = function (string $event, array $payload): void {
                echo "event: {$event}\n";
                echo 'data: ' . json_encode($payload) . "\n\n";
                @ob_flush();
                flush();
            };

            $serverUrl = rtrim(env('UPDATE_SERVER_URL', ''), '/');
            $authToken = env('UPDATE_AUTH_TOKEN', '');

            if (empty($serverUrl) || empty($authToken)) {
                $emit('done', ['success' => false, 'output' => 'UPDATE_SERVER_URL and UPDATE_AUTH_TOKEN must be set in .env']);
                return;
            }

            // ── Fetch release info ────────────────────────────────────
            $emit('progress', ['pct' => 5, 'label' => 'Checking release info\u2026', 'output' => '']);
            try {
                $infoRes = Http::withToken($authToken)->timeout(30)
                    ->get("{$serverUrl}/superadmin/update/release-info");
            } catch (\Throwable $e) {
                $emit('done', ['success' => false, 'output' => 'Cannot reach update server: ' . $e->getMessage()]);
                return;
            }

            if (! $infoRes->successful()) {
                $emit('done', ['success' => false, 'output' => 'Release-info HTTP ' . $infoRes->status()]);
                return;
            }

            $info          = $infoRes->json();
            $remoteVersion = $info['version']      ?? '';
            $downloadUrl   = $info['download_url'] ?? '';
            $sha256        = $info['sha256']        ?? '';

            if (empty($remoteVersion) || empty($downloadUrl)) {
                $emit('done', ['success' => false, 'output' => 'Invalid release-info from server.']);
                return;
            }

            // ── Download ──────────────────────────────────────────────
            $emit('progress', ['pct' => 15, 'label' => "Downloading v{$remoteVersion}\u2026", 'output' => "Remote version: {$remoteVersion}\n"]);
            $zipPath = storage_path("app/releases/update-{$remoteVersion}.zip");
            @mkdir(dirname($zipPath), 0775, true);

            try {
                $dlRes = Http::withToken($authToken)->timeout(300)->sink($zipPath)->get($downloadUrl);
            } catch (\Throwable $e) {
                @unlink($zipPath);
                $emit('done', ['success' => false, 'output' => 'Download error: ' . $e->getMessage()]);
                return;
            }

            if (! $dlRes->successful()) {
                @unlink($zipPath);
                $emit('done', ['success' => false, 'output' => 'Download HTTP ' . $dlRes->status()]);
                return;
            }

            // ── Verify checksum ───────────────────────────────────────
            $emit('progress', ['pct' => 35, 'label' => 'Verifying package\u2026', 'output' => '']);
            if (! empty($sha256)) {
                $actual = hash_file('sha256', $zipPath);
                if (! hash_equals($sha256, $actual)) {
                    @unlink($zipPath);
                    $emit('done', ['success' => false, 'output' => "Checksum mismatch. Expected {$sha256}, got {$actual}"]);
                    return;
                }
            }
            $emit('progress', ['pct' => 40, 'label' => 'Checksum verified \u2713', 'output' => '']);

            // ── Extract ───────────────────────────────────────────────
            $emit('progress', ['pct' => 45, 'label' => 'Extracting package\u2026', 'output' => '']);
            $extractDir = storage_path("app/releases/extract-{$remoteVersion}");
            @mkdir($extractDir, 0775, true);

            $zip = new \ZipArchive;
            if ($zip->open($zipPath) !== true) {
                @unlink($zipPath);
                $emit('done', ['success' => false, 'output' => 'Failed to open zip archive.']);
                return;
            }
            $zip->extractTo($extractDir);
            $zip->close();
            @unlink($zipPath);

            // ── Copy files ────────────────────────────────────────────
            $emit('progress', ['pct' => 55, 'label' => 'Applying new files\u2026', 'output' => '']);
            $this->copyExtractedFiles($extractDir, base_path());
            $this->deleteDirectory($extractDir);
            $emit('progress', ['pct' => 62, 'label' => 'Files applied \u2713 — running deploy\u2026', 'output' => '']);

            // ── pos:deploy steps ──────────────────────────────────────
            $deploySteps = [
                ['label' => 'Running pos:setup\u2026',           'pct' => 66, 'run' => function () { Artisan::call('pos:setup', ['--force' => true]); return Artisan::output(); }],
                ['label' => 'Applying migrations\u2026',         'pct' => 72, 'run' => function () { Artisan::call('migrate', ['--force' => true]); return Artisan::output(); }],
                ['label' => 'Migrating modules\u2026',           'pct' => 77, 'run' => function () { Artisan::call('module:migrate', ['--force' => true]); return Artisan::output(); }],
                ['label' => 'Publishing module assets\u2026',    'pct' => 81, 'run' => function () {
                    try {
                        Artisan::call('module:publish');
                        return Artisan::output();
                    } catch (\Throwable $e) {
                        Log::warning('module:publish skipped during update: ' . $e->getMessage());
                        return 'module:publish skipped: ' . $e->getMessage();
                    }
                }],
                ['label' => 'Seeding permissions\u2026',         'pct' => 85, 'run' => function () { Artisan::call('db:seed', ['--class' => 'PermissionsTableSeeder', '--force' => true]); return Artisan::output(); }],
                ['label' => 'Installing Passport keys\u2026',    'pct' => 88, 'run' => function () { Artisan::call('passport:install', ['--force' => true]); return Artisan::output(); }],
                ['label' => 'Resetting permission cache\u2026',  'pct' => 91, 'run' => function () { Artisan::call('permission:cache-reset'); return Artisan::output(); }],
                ['label' => 'Caching config & routes\u2026',     'pct' => 95, 'run' => function () {
                    $out = '';
                    foreach (['config:cache' => 'config:clear', 'route:cache' => 'route:clear', 'view:cache' => 'view:clear'] as $try => $fallback) {
                        try { Artisan::call($try); $out .= Artisan::output(); }
                        catch (\Throwable $e) { Artisan::call($fallback); $out .= Artisan::output(); }
                    }
                    return $out;
                }],
                ['label' => 'Finalising\u2026', 'pct' => 97, 'run' => function () use ($remoteVersion) {
                    $authorConfig = @include config_path('author.php');
                    $version = (is_array($authorConfig) && isset($authorConfig['app_version']))
                        ? $authorConfig['app_version'] : $remoteVersion;
                    System::updateOrCreate(['key' => 'app_version'], ['value' => $version]);
                    return "Version {$version} stamped.\n";
                }],
            ];

            $allOutput = '';
            $success   = true;

            foreach ($deploySteps as $step) {
                $emit('progress', ['pct' => $step['pct'], 'label' => $step['label'], 'output' => '']);
                try {
                    $output     = ($step['run'])();
                    $allOutput .= $output;
                    $emit('progress', ['pct' => $step['pct'], 'label' => $step['label'] . ' \u2713', 'output' => $output]);
                } catch (\Throwable $e) {
                    Log::error('pos:pull-update deploy step failed: ' . $step['label'] . ' — ' . $e->getMessage());
                    $allOutput .= $e->getMessage();
                    $emit('stepError', ['pct' => $step['pct'], 'label' => $step['label'] . ' — failed', 'output' => $e->getMessage()]);
                    $success = false;
                    break;
                }
            }

            $emit('done', ['success' => $success, 'output' => $allOutput, 'pct' => $success ? 100 : 97]);
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    /**
     * Webhook endpoint: receives a signed push trigger from the central server.
     * Validates HMAC-SHA256 signature, caches the pending update, and optionally
     * kicks off pos:pull-update in the background.
     * Requires UPDATE_WEBHOOK_SECRET in .env on the client server.
     * No CSRF — this is an API endpoint authenticated by HMAC.
     */
    public function triggerWebhook(Request $request): JsonResponse
    {
        $secret = trim((string) env('UPDATE_WEBHOOK_SECRET', ''));
        $clientAuthToken = trim((string) env('UPDATE_AUTH_TOKEN', ''));
        if ($clientAuthToken === '') {
            // Backward compatibility: some clients still store the shared token in UPDATE_DOWNLOAD_TOKEN.
            $clientAuthToken = trim((string) env('UPDATE_DOWNLOAD_TOKEN', ''));
        }
        $bearer = $request->bearerToken();
        $tokenValid = $clientAuthToken !== '' && $bearer !== null && hash_equals($clientAuthToken, $bearer);

        if ($secret === '' && ! $tokenValid) {
            return response()->json([
                'error' => 'Webhook auth not configured. Set UPDATE_WEBHOOK_SECRET or UPDATE_AUTH_TOKEN (or UPDATE_DOWNLOAD_TOKEN) on this server.',
            ], 503);
        }

        $rawBody  = $request->getContent();
        $sigHeader = $request->header('X-Update-Signature', '');
        $signatureValid = false;
        if ($secret !== '') {
            $expected  = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
            $signatureValid = hash_equals($expected, $sigHeader);
        }

        if (! $signatureValid && ! $tokenValid) {
            Log::warning('Update webhook: invalid signature from ' . $request->ip());
            return response()->json(['error' => 'Invalid signature.'], 401);
        }

        $data    = $request->json()->all();
        $version = $data['version'] ?? 'unknown';

        // Cache the pending update so the UI can surface a notification.
        Cache::put('pending_pull_update', [
            'version'     => $version,
            'received_at' => now()->toIso8601String(),
        ], now()->addHours(6));

        Log::info("Update webhook received: v{$version} push from central ({$request->ip()}).");

        // Kick off pos:pull-update in the background; return 500 when it cannot be launched.
        [$started, $launchMessage] = $this->startBackgroundPullUpdate();
        if (! $started) {
            Log::error('Update webhook received but pull-update could not be started: ' . $launchMessage);
            return response()->json([
                'accepted' => false,
                'error' => 'Webhook received, but client deploy process could not be started.',
                'message' => $launchMessage,
            ], 500);
        }

        return response()->json([
            'accepted' => true,
            'version' => $version,
            'message' => $launchMessage,
        ]);
    }

    /**
     * Status report from client — called after pos:pull-update completes (success or failure).
     * Authenticated by UPDATE_AUTH_TOKEN bearer token.
     * Updates the central server's client registry with completion status.
     */
    public function reportUpdateStatus(Request $request): JsonResponse
    {
        // CENTRAL SERVER: Verify bearer token (client's UPDATE_AUTH_TOKEN must match our UPDATE_DOWNLOAD_TOKEN)
        if (! $this->authorizeDownloadToken($request)) {
            Log::warning('Update status report: invalid/missing auth token from ' . $request->ip());
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $data   = $request->json()->all();
        $status = $data['status'] ?? 'unknown'; // 'success' or 'failed'
        $version = $data['version'] ?? 'unknown';
        $message = $data['message'] ?? '';
        $clientUrl = rtrim((string) ($data['client_url'] ?? ''), '/');
        $clientIp = $request->ip();

        // Preferred matching: explicit URL sent by the client callback.
        $client = null;
        if ($clientUrl !== '') {
            $client = UpdateClient::where('url', $clientUrl)->first();
        }

        // Fallback matching by normalized URL (ignores scheme, trailing slash, and optional /public suffix).
        if (! $client && $clientUrl !== '') {
            $incomingKey = $this->normalizeClientUrlKey($clientUrl);
            if ($incomingKey !== '') {
                $allClients = UpdateClient::get(['id', 'name', 'url']);
                foreach ($allClients as $candidate) {
                    if ($this->normalizeClientUrlKey((string) $candidate->url) === $incomingKey) {
                        $client = $candidate;
                        break;
                    }
                }
            }
        }

        // Fallback matching by host when URL keys are close but not identical.
        if (! $client && $clientUrl !== '') {
            $incomingHost = strtolower((string) parse_url($clientUrl, PHP_URL_HOST));
            if ($incomingHost !== '') {
                $allClients = UpdateClient::get(['id', 'name', 'url']);
                $hostMatches = [];
                foreach ($allClients as $candidate) {
                    $candidateHost = strtolower((string) parse_url((string) $candidate->url, PHP_URL_HOST));
                    if ($candidateHost === $incomingHost) {
                        $hostMatches[] = $candidate;
                    }
                }

                if (count($hostMatches) === 1) {
                    $client = $hostMatches[0];
                }
            }
        }

        // Fallback matching by host when URL is unavailable/mismatched.
        if (! $client) {
            $allClients = UpdateClient::get(['id', 'name', 'url']);
            foreach ($allClients as $candidate) {
                $host = parse_url((string) $candidate->url, PHP_URL_HOST);
                if (! $host) {
                    continue;
                }

                $resolved = @gethostbyname($host);
                if ($resolved === $clientIp) {
                    $client = $candidate;
                    break;
                }
            }
        }

        if (! $client) {
            // Fallback: just log the status report without updating a specific client record.
            Log::info("Update status report received but client not identified: v{$version} {$status} from {$clientIp} (client_url={$clientUrl})");
            return response()->json(['accepted' => true, 'message' => 'Status logged']);
        }

        // Update the client record with the final status
        $client->update([
            'last_push_status' => $status === 'success' ? 'success' : 'failed',
            'last_version'     => $status === 'success' ? $version : $client->last_version,
            'last_pushed_at'   => now(),
        ]);

        $logMsg = "Update report from [{$client->name}]: v{$version} {$status}";
        if ($message) {
            $logMsg .= " — {$message}";
        }
        Log::info($logMsg);

        return response()->json(['accepted' => true, 'message' => "Status updated: {$status}"]);
    }

    /** Normalize client URLs for robust callback matching. */
    private function normalizeClientUrlKey(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return '';
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $path = '/' . trim($path, '/');
        if ($path === '/') {
            $path = '';
        }

        // Optional deployment suffix frequently differs between app.url and registry URLs.
        $path = preg_replace('#/public$#i', '', $path) ?? $path;

        return rtrim($host . $path, '/');
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /** Verify the static bearer token used for release downloads. */
    private function authorizeDownloadToken(Request $request): bool
    {
        $serverToken = $this->getActiveDownloadToken();
        if (empty($serverToken)) {
            return false;
        }
        $bearer = $request->bearerToken();
        return $bearer !== null && hash_equals($serverToken, $bearer);
    }

    /** Launch pos:pull-update --force in the background on the client instance. */
    private function startBackgroundPullUpdate(): array
    {
        $phpBin = $this->resolvePhpBinary();
        $artisan = escapeshellarg(base_path('artisan'));
        $logPath = storage_path('logs/pull-update-' . date('YmdHis') . '.log');
        $logArg = escapeshellarg($logPath);
        $cmd = escapeshellarg($phpBin) . " {$artisan} pos:pull-update --force >> {$logArg} 2>&1";

        // Prefer shell_exec to capture PID for observability.
        if ($this->isShellFunctionAvailable('shell_exec')) {
            $pid = trim((string) @shell_exec("{$cmd} & echo $!"));
            if ($pid !== '' && ctype_digit($pid)) {
                return [true, "pull-update started (PID {$pid}); log: {$logPath}"];
            }
        }

        // Fallback to exec when shell_exec is unavailable.
        if ($this->isShellFunctionAvailable('exec')) {
            @exec("{$cmd} &", $output, $exitCode);
            if ((int) $exitCode === 0) {
                return [true, "pull-update started; log: {$logPath}"];
            }

            return [false, 'Background process launch failed (exec exit code ' . (int) $exitCode . ').'];
        }

        return [false, 'Background process launch is unavailable (exec/shell_exec disabled in PHP).'];
    }

    /** Pick an executable PHP binary path for background command launch. */
    private function resolvePhpBinary(): string
    {
        $phpBin = PHP_BINARY;
        if (! empty($phpBin) && is_file($phpBin) && is_executable($phpBin)) {
            return $phpBin;
        }

        $fallback = trim((string) @shell_exec('command -v php8.4 2>/dev/null'))
            ?: trim((string) @shell_exec('command -v php 2>/dev/null'))
            ?: '/usr/bin/php';

        return $fallback;
    }

    /** Whether a shell function is callable under current PHP disable_functions policy. */
    private function isShellFunctionAvailable(string $functionName): bool
    {
        if (! function_exists($functionName)) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        return ! in_array($functionName, $disabled, true);
    }

    /** Returns active download token; DB-managed token overrides .env token. */
    private function getActiveDownloadToken(): string
    {
        $envToken = trim((string) env('UPDATE_DOWNLOAD_TOKEN', ''));
        if ($envToken !== '') {
            return $envToken;
        }

        $managed = (string) (System::getProperty('update_download_token') ?? '');
        if ($managed !== '') {
            return $managed;
        }

        return '';
    }

    /** Load the release manifest written by pos:package-release. */
    private function loadManifest(): ?array
    {
        $path = storage_path('app/releases/manifest.json');
        if (! file_exists($path)) {
            return null;
        }
        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : null;
    }

    /** Enumerate release zip files sorted by highest semantic version first. */
    private function listReleasePackages(): array
    {
        $dir = storage_path('app/releases');
        if (! is_dir($dir)) {
            return [];
        }

        $files = glob($dir . '/v*.zip') ?: [];
        $packages = [];

        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }

            $filename = basename($file);
            $version = preg_replace('/^v(.+)\\.zip$/', '$1', $filename);
            if (! is_string($version) || $version === $filename || $version === '') {
                continue;
            }

            $packages[] = [
                'filename' => $filename,
                'version' => $version,
                'size_kb' => (int) round(filesize($file) / 1024),
                'modified_at' => date('c', filemtime($file) ?: time()),
            ];
        }

        usort($packages, function (array $a, array $b): int {
            return version_compare($b['version'], $a['version']);
        });

        return $packages;
    }

    /** Send an HMAC-signed update push to one client and update its DB record. */
    private function dispatchPush(UpdateClient $client): array
    {
        try {
            $manifest = $this->loadManifest();
            $version  = $manifest ? $manifest['version'] : config('author.app_version', '0');
            $downloadToken = $this->getActiveDownloadToken();

            $payload = json_encode([
                'version'      => $version,
                'download_url' => route('superadmin.update.package'),
                'pushed_at'    => now()->toIso8601String(),
            ]);

            $signature = 'sha256=' . hash_hmac('sha256', $payload, $client->webhook_secret);

            $headers = [
                'X-Update-Signature' => $signature,
                'Content-Type'       => 'application/json',
                'Accept'             => 'application/json',
            ];
            if (! empty($downloadToken)) {
                $headers['Authorization'] = 'Bearer ' . $downloadToken;
            }

            $response = Http::withHeaders($headers)
                ->timeout(15)
                ->post($client->url . '/api/update/trigger', json_decode($payload, true));

            $ok = $response->successful();
            $errorDetail = '';
            if (! $ok) {
                $errorDetail = (string) ($response->json('error') ?? $response->json('message') ?? '');
                $errorDetail = trim($errorDetail);
            }
            $client->update([
                'last_version'     => $ok ? $version : $client->last_version,
                'last_push_status' => $ok ? 'pending' : 'failed',
                'last_pushed_at'   => now(),
            ]);

            $msg = $ok
                ? 'Trigger accepted.'
                : ('HTTP ' . $response->status() . ($errorDetail !== '' ? ': ' . $errorDetail : ''));

            if (! $ok && $response->status() === 503 && str_contains(strtolower($errorDetail), 'webhook not configured')) {
                $msg .= ' Configure client .env with UPDATE_WEBHOOK_SECRET=' . $client->webhook_secret
                    . ' and run php artisan optimize:clear on the client server.';
            }

            return [$ok, $msg];
        } catch (\Throwable $e) {
            $client->update(['last_push_status' => 'failed', 'last_pushed_at' => now()]);
            Log::error("Push to [{$client->name}] failed: " . $e->getMessage());
            return [false, $e->getMessage()];
        }
    }

    /** Copy extracted files to dest, skipping protected paths. */
    private function copyExtractedFiles(string $source, string $dest): void
    {
        $source    = rtrim(realpath($source) ?: $source, '/');
        $dest      = rtrim(realpath($dest)   ?: $dest,   '/');
        $protected = ['.env', '.env.production', 'storage/app/', 'storage/logs/', 'public/uploads/', 'bootstrap/cache/'];

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $item) {
            $rel = ltrim(substr($item->getPathname(), strlen($source)), '/');
            foreach ($protected as $guard) {
                $guard = rtrim($guard, '/');
                if ($rel === $guard || str_starts_with($rel, $guard . '/')) {
                    continue 2;
                }
            }
            $target = $dest . '/' . $rel;
            $item->isDir()
                ? @mkdir($target, 0775, true)
                : (@mkdir(dirname($target), 0775, true) && copy($item->getPathname(), $target));
        }
    }

    /** Recursively delete a directory. */
    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }
}
