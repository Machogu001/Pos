<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * pos:setup
 *
 * Idempotent post-install setup command. Safe to run multiple times.
 * Run automatically by the web installer, and can be re-run manually:
 *
 *   php artisan pos:setup
 *
 * What it does:
 *   1. Generates APP_KEY if missing
 *   2. Creates all required storage/public directories with correct permissions
 *   3. Creates the storage:link symlink (public/storage → storage/app/public)
 *   4. Generates Passport OAuth keys if missing
 *   5. Creates bootstrap/cache if missing
 *   6. Sets storage/ and bootstrap/cache/ to writable permissions
 *   7. Clears and re-caches config/routes/views
 */
class AppSetup extends Command
{
    protected $signature   = 'pos:setup {--force : Skip confirmation prompts}';
    protected $description = 'Run all post-install setup tasks (directories, symlinks, keys, cache).';

    public function handle(): int
    {
        $this->info('Running POS setup...');

        $this->ensureAppKey();
        $this->ensureDirectories();
        $this->ensureStoragePermissions();
        $this->ensureLanguagePermissions();
        $this->ensureModuleStatusFile();
        $this->ensureLogFileExists();
        $this->ensureStorageLink();
        $this->ensurePassportKeys();
        $this->ensureBootstrapCache();
        $this->clearCaches();
        $this->rediscoverPackages();
        $this->runPosHeaderHealthCheck();

        $this->info('');
        $this->info('Setup complete.');

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------

    private function ensureAppKey(): void
    {
        if (empty(config('app.key'))) {
            $this->line('  Generating APP_KEY...');
            Artisan::call('key:generate', ['--force' => true]);
            $this->line('  APP_KEY generated.');
        } else {
            $this->line('  APP_KEY: already set.');
        }
    }

    private function ensureDirectories(): void
    {
        $dirs = [
            // Storage framework
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('framework/testing'),
            // Application logs
            storage_path('logs'),
            // App uploads
            storage_path('app/public'),
            storage_path('app/public/business_logos'),
            storage_path('app/public/company_logos'),
            storage_path('app/public/product_images'),
            storage_path('app/public/invoice_logos'),
            storage_path('app/public/documents'),
            storage_path('app/public/media'),
            // Public upload paths used directly
            public_path('uploads'),
            public_path('business_logos'),
            public_path('invoice_logos'),
            public_path('img'),
            public_path('temp'),
            public_path('documents'),
            public_path('media'),
        ];

        foreach ($dirs as $dir) {
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0775, true, true);
                $this->line("  Created: {$dir}");
            }
        }

        // Ensure top-level storage/ and bootstrap/cache/ are writable
        @chmod(storage_path(), 0775);
        @chmod(base_path('bootstrap/cache'), 0775);

        $this->line('  Directories: OK');
    }

    private function ensureStorageLink(): void
    {
        $link   = public_path('storage');
        $target = storage_path('app/public');

        if (is_link($link)) {
            // Check if it points to the correct target
            if (realpath($link) === realpath($target)) {
                $this->line('  Storage symlink: already correct.');
                return;
            }
            // Stale symlink — remove and recreate
            unlink($link);
            $this->line('  Storage symlink: removed stale link.');
        } elseif (File::isDirectory($link)) {
            $this->warn("  Storage symlink: {$link} is a real directory, skipping.");
            return;
        }

        symlink($target, $link);
        $this->line("  Storage symlink: {$link} → {$target}");
    }

    private function ensurePassportKeys(): void
    {
        $private = storage_path('oauth-private.key');
        $public  = storage_path('oauth-public.key');

        if (file_exists($private) && file_exists($public)) {
            $this->line('  Passport keys: already exist.');
            return;
        }

        try {
            Artisan::call('passport:keys', ['--force' => true]);
            $this->line('  Passport keys: generated.');
        } catch (\Throwable $e) {
            $this->warn('  Passport keys: could not generate — ' . $e->getMessage());
        }
    }

    private function ensureBootstrapCache(): void
    {
        $dir = base_path('bootstrap/cache');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0775, true, true);
            $this->line("  Created: {$dir}");
        }

        // Ensure the .gitignore placeholder exists so git tracks the directory
        $gitignore = $dir . '/.gitignore';
        if (! file_exists($gitignore)) {
            file_put_contents($gitignore, "*\n!.gitignore\n");
        }

        $this->line('  bootstrap/cache: OK');
    }

    /**
     * Recursively fix permissions on storage subdirectories and existing files
     * so both CLI users and the web server (www-data) can read/write.
     *
     * Directories → 0775 + setgid (new files inherit the directory group)
     * Files       → 0664
     *
     * Uses only PHP's chmod() — no shell exec, no root required.
     * The setgid bit ensures files created by any user inherit the group.
     */
    private function ensureStoragePermissions(): void
    {
        $targets = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ];

        foreach ($targets as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            // Directory itself: rwxrwsr-x (setgid so new files inherit group)
            @chmod($dir, 02775);

            // All existing files in the directory
            foreach (new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS) as $entry) {
                if ($entry->isFile()) {
                    @chmod($entry->getPathname(), 0664);
                }
            }
        }

        $this->line('  Storage permissions: OK');
    }

    /**
     * Ensure translation files are readable by the web server.
     *
     * Generated locale files can inherit a restrictive umask, which breaks
     * Laravel's translator on the next request if they end up at 0600.
     */
    private function ensureLanguagePermissions(): void
    {
        $langRoot = base_path('lang');

        if (! is_dir($langRoot)) {
            $this->line('  Language permissions: skipped (lang/ missing).');
            return;
        }

        @chmod($langRoot, 0755);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($langRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $entry) {
            $path = $entry->getPathname();

            if ($entry->isDir()) {
                @chmod($path, 0755);
                continue;
            }

            if ($entry->isFile()) {
                @chmod($path, 0644);
            }
        }

        $this->line('  Language permissions: OK');
    }

    /**
     * Ensure module enablement state survives restarts.
     *
     * The app stores Nwidart module activation in modules_statuses.json.
     * If this file is recreated with restrictive permissions, module checkboxes
     * can appear to reset on the next request.
     */
    private function ensureModuleStatusFile(): void
    {
        $statusFile = base_path('modules_statuses.json');

        if (! file_exists($statusFile)) {
            file_put_contents($statusFile, "{}\n");
        }

        @chmod($statusFile, 0664);

        if (! is_writable($statusFile)) {
            $this->warn('  Module status file: could not be made writable.');
            return;
        }

        $this->line('  Module status file: OK');
    }

    /**
     * Run a post-setup diagnostic to catch POS header visibility drift early.
     */
    private function runPosHeaderHealthCheck(): void
    {
        $this->line('  Running POS header health check...');

        try {
            $exitCode = Artisan::call('pos:health:header');
            $output = trim(Artisan::output());

            if ($output !== '') {
                $this->line($output);
            }

            if ($exitCode !== 0) {
                $this->warn('  POS header health check reported issues. Please review output above.');
            } else {
                $this->line('  POS header health check: OK');
            }
        } catch (\Throwable $e) {
            $this->warn('  POS header health check skipped: ' . $e->getMessage());
        }
    }

    private function clearCaches(): void
    {
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('cache:clear');
        $this->line('  Caches cleared.');
    }

    /**
     * Re-run package:discover so bootstrap/cache/packages.php and
     * bootstrap/cache/services.php are always in sync with the installed
     * vendor packages. Stale cache files are the most common cause of the
     * "Class SentinelServiceProvider not found" error on fresh installs.
     */
    private function rediscoverPackages(): void
    {
        try {
            Artisan::call('package:discover', ['--ansi' => false]);
            $this->line('  Package discovery: OK');
        } catch (\Throwable $e) {
            $this->warn('  Package discovery: failed — ' . $e->getMessage());
        }
    }

    /**
     * Pre-create today's log file owned by the current process user with
     * group-writable permissions (0664). This prevents the "chmod():
     * Operation not permitted" error that occurs when www-data tries to
     * chmod a file it didn't create, or vice-versa for CLI users.
     *
     * With 'permission => null' in config/logging.php, Monolog no longer
     * calls chmod() at all — but we still seed the file here so it exists
     * with correct permissions before either user writes to it.
     */
    private function ensureLogFileExists(): void
    {
        $logDir  = storage_path('logs');
        $logFile = $logDir . '/laravel.log';

        if (! file_exists($logFile)) {
            touch($logFile);
            @chmod($logFile, 0664);
            $this->line('  Log file created: ' . $logFile);
        } else {
            // Ensure it's group-writable even if it already exists and is owned by us
            @chmod($logFile, 0664);
            $this->line('  Log file: OK');
        }
    }
}
