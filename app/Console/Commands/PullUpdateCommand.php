<?php

namespace App\Console\Commands;

use App\System;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use ZipArchive;

/**
 * pos:pull-update
 *
 * Downloads the latest release from the central update server (UPDATE_SERVER_URL),
 * verifies the SHA-256 checksum, extracts the archive, copies files to the app
 * root (skipping protected paths), then runs pos:deploy.
 *
 * Required .env on this (client) server:
 *   UPDATE_SERVER_URL=https://central.example.com
 *   UPDATE_AUTH_TOKEN=<token matching central's UPDATE_DOWNLOAD_TOKEN>
 */
class PullUpdateCommand extends Command
{
    protected $signature   = 'pos:pull-update {--force : Skip version comparison and force download}';
    protected $description = 'Download and apply the latest release from the central update server.';

    /** Paths relative to base_path() that are never overwritten during extraction. */
    private const PROTECTED = [
        '.env',
        '.env.production',
        'storage/app/',
        'storage/logs/',
        'public/uploads/',
        'bootstrap/cache/',
    ];

    public function handle(): int
    {
        $serverUrl = rtrim(env('UPDATE_SERVER_URL', ''), '/');
        $authToken = env('UPDATE_AUTH_TOKEN', '');

        if (empty($serverUrl) || empty($authToken)) {
            $this->error('UPDATE_SERVER_URL and UPDATE_AUTH_TOKEN must be set in .env');
            return self::FAILURE;
        }

        // ── 1. Fetch release info ─────────────────────────────────────
        $this->line('==> Checking release info…');
        try {
            $infoRes = Http::withToken($authToken)->timeout(30)
                ->get("{$serverUrl}/superadmin/update/release-info");
        } catch (\Throwable $e) {
            $this->error('Cannot reach update server: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (! $infoRes->successful()) {
            $this->error('Update server returned HTTP ' . $infoRes->status());
            return self::FAILURE;
        }

        $info          = $infoRes->json();
        $remoteVersion = $info['version']      ?? '';
        $downloadUrl   = $info['download_url'] ?? '';
        $sha256        = $info['sha256']        ?? '';

        if (empty($remoteVersion) || empty($downloadUrl)) {
            $this->error('Invalid release-info response.');
            return self::FAILURE;
        }

        $installedVersion = System::getProperty('app_version') ?? '';
        if (! $this->option('force')
            && $installedVersion !== ''
            && version_compare($remoteVersion, $installedVersion, '<=')
        ) {
            $this->info("Already on v{$installedVersion} — nothing to do. Use --force to re-download.");
            return self::SUCCESS;
        }

        // ── 2. Download ───────────────────────────────────────────────
        $this->line("==> Downloading v{$remoteVersion}…");
        $zipPath = storage_path("app/releases/update-{$remoteVersion}.zip");
        @mkdir(dirname($zipPath), 0775, true);

        try {
            $dlRes = Http::withToken($authToken)->timeout(300)->sink($zipPath)->get($downloadUrl);
        } catch (\Throwable $e) {
            @unlink($zipPath);
            $this->error('Download failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (! $dlRes->successful()) {
            @unlink($zipPath);
            $this->error('Download returned HTTP ' . $dlRes->status());
            return self::FAILURE;
        }

        // ── 3. Checksum ───────────────────────────────────────────────
        if (! empty($sha256)) {
            $this->line('==> Verifying checksum…');
            $actual = hash_file('sha256', $zipPath);
            if (! hash_equals($sha256, $actual)) {
                @unlink($zipPath);
                $this->error("Checksum mismatch! Expected {$sha256}, got {$actual}");
                return self::FAILURE;
            }
            $this->line('    Checksum OK ✓');
        }

        // ── 4. Extract ────────────────────────────────────────────────
        $this->line('==> Extracting…');
        $extractDir = storage_path("app/releases/extract-{$remoteVersion}");
        @mkdir($extractDir, 0775, true);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            @unlink($zipPath);
            $this->error('Failed to open zip archive.');
            return self::FAILURE;
        }
        $zip->extractTo($extractDir);
        $zip->close();
        @unlink($zipPath);

        // ── 5. Copy files ─────────────────────────────────────────────
        $this->line('==> Applying new files…');
        $this->copyFiles($extractDir, base_path());
        $this->removeDir($extractDir);

        // ── 6. Deploy ─────────────────────────────────────────────────
        $this->line('==> Running pos:deploy…');
        $exitCode = Artisan::call('pos:deploy', ['--force' => true]);
        $this->line(Artisan::output());

        if ($exitCode !== 0) {
            $this->error('pos:deploy finished with errors.');
            return self::FAILURE;
        }

        $this->info("==> pos:pull-update complete. Now on v{$remoteVersion}.");
        return self::SUCCESS;
    }

    private function copyFiles(string $src, string $dst): void
    {
        $src = rtrim(realpath($src) ?: $src, '/');
        $dst = rtrim(realpath($dst) ?: $dst, '/');

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $item) {
            $rel = ltrim(substr($item->getPathname(), strlen($src)), '/');

            foreach (self::PROTECTED as $guard) {
                $guard = rtrim($guard, '/');
                if ($rel === $guard || str_starts_with($rel, $guard . '/')) {
                    continue 2;
                }
            }

            $target = $dst . '/' . $rel;
            $item->isDir()
                ? @mkdir($target, 0775, true)
                : ((@mkdir(dirname($target), 0775, true)) && copy($item->getPathname(), $target));
        }
    }

    private function removeDir(string $dir): void
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
