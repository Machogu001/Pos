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
        $this->ensureStorageLink();
        $this->ensurePassportKeys();
        $this->ensureBootstrapCache();
        $this->clearCaches();

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

        // Ensure storage/ and bootstrap/cache/ are writable by web server
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

    private function clearCaches(): void
    {
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('cache:clear');
        $this->line('  Caches cleared.');
    }
}
