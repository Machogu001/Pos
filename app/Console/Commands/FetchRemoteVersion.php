<?php

namespace App\Console\Commands;

use App\System;
use Composer\Semver\Comparator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchRemoteVersion extends Command
{
    protected $signature   = 'pos:fetchRemoteVersion';
    protected $description = 'Fetch the latest available version from the update server and cache it locally.';

    public function handle(): int
    {
        $url = config('author.update_check_url', env('UPDATE_CHECK_URL', ''));

        if (empty($url)) {
            $this->info('UPDATE_CHECK_URL is not configured. Skipping remote version check.');
            return 0;
        }

        try {
            $response = Http::timeout(10)->get($url);

            if (! $response->successful()) {
                Log::warning('FetchRemoteVersion: HTTP ' . $response->status() . ' from ' . $url);
                $this->warn('Update server returned HTTP ' . $response->status());
                return 1;
            }

            $data    = $response->json();
            $version = $data['version'] ?? null;

            if (empty($version)) {
                Log::warning('FetchRemoteVersion: No "version" field in response from ' . $url);
                $this->warn('No "version" field in update server response.');
                return 1;
            }

            // Only store if it's a valid semver-ish string
            if (! preg_match('/^\d+(\.\d+)*$/', $version)) {
                Log::warning("FetchRemoteVersion: Invalid version string '{$version}' from {$url}");
                $this->warn("Invalid version string: {$version}");
                return 1;
            }

            System::updateOrCreate(
                ['key' => 'remote_available_version'],
                ['value' => $version]
            );

            $installedVersion = System::getProperty('app_version') ?? '0';
            $isNewer = Comparator::greaterThan($version, $installedVersion);

            $this->info("Remote version: {$version} | Installed: {$installedVersion} | Update available: " . ($isNewer ? 'YES' : 'no'));
            Log::info("FetchRemoteVersion: remote={$version}, installed={$installedVersion}, update_available=" . ($isNewer ? 'true' : 'false'));

        } catch (\Throwable $e) {
            Log::error('FetchRemoteVersion: Failed to fetch ' . $url . ' — ' . $e->getMessage());
            $this->error('Failed to reach update server: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
