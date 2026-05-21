<?php

namespace App\Console\Commands;

use App\System;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * pos:deploy
 *
 * Idempotent deployment command. Use after git pull / package updates.
 * Safe to run multiple times. Does NOT wipe the database.
 *
 *   php artisan pos:deploy
 *   php artisan pos:deploy --fresh   # WARNING: drops all tables + re-seeds
 *
 * Sequence (--fresh):
 *   1. pos:setup          — dirs, symlinks, Passport keys, cache clear
 *   2. migrate:fresh      — drop + re-run all core migrations
 *   3. module:migrate     — run any module migrations not auto-discovered
 *   4. module:publish     — publish module assets (CSS/JS/views)
 *   5. db:seed            — seed barcodes, permissions, currencies, admin, superadmin
 *   6. passport:install   — create OAuth clients in DB
 *   7. permission:cache-reset
 *   8. optimize
 *
 * Sequence (default update):
 *   1. pos:setup          — dirs, symlinks, Passport keys
 *   2. migrate            — run new core migrations
 *   3. module:migrate     — run new module migrations
 *   4. module:publish     — publish module assets
 *   5. passport:install   — ensure OAuth clients exist
 *   6. db:seed PermissionsTableSeeder — add any new permissions
 *   7. permission:cache-reset
 *   8. optimize
 */
class DeployCommand extends Command
{
    protected $signature   = 'pos:deploy
                                {--fresh : Drop all tables and re-seed (use on a fresh database only)}
                                {--force : Skip confirmation prompts}';

    protected $description = 'Run all deployment steps: migrate, publish assets, seed, cache.';

    public function handle(): int
    {
        $isFresh = $this->option('fresh');
        $force   = $this->option('force');

        if ($isFresh && ! $force) {
            $confirmed = $this->confirm(
                'WARNING: --fresh will drop ALL database tables and re-seed from scratch. Continue?',
                false
            );
            if (! $confirmed) {
                $this->info('Aborted.');
                return self::FAILURE;
            }
        }

        $this->info('==> pos:deploy starting' . ($isFresh ? ' (--fresh)' : '') . ' ...');

        // Step 1: Setup dirs, symlinks, Passport keys, clear caches
        $this->step('pos:setup (dirs, symlinks, keys)', function () use ($force) {
            Artisan::call('pos:setup', ['--force' => true]);
        });

        DB::statement('SET default_storage_engine=INNODB;');

        if ($isFresh) {
            // Step 2a: Drop all + re-run all core migrations
            $this->step('migrate:fresh', function () {
                Artisan::call('migrate:fresh', ['--force' => true]);
            });
        } else {
            // Step 2b: Run only new core migrations
            $this->step('migrate', function () {
                Artisan::call('migrate', ['--force' => true]);
            });
        }

        // Step 3: Module migrations (belt-and-suspenders; safe no-op if already run)
        $this->step('module:migrate', function () {
            Artisan::call('module:migrate', ['--force' => true]);
        });

        // Step 4: Publish module assets
        $this->step('module:publish', function () {
            try {
                Artisan::call('module:publish');
                $this->line('    module:publish ✓');
            } catch (\Throwable $e) {
                // Some nwidart/laravel-modules versions are not compatible with newer
                // Laravel console internals. Do not fail deployment for asset publishing.
                $this->warn('    module:publish skipped (' . $e->getMessage() . ')');
            }
        });

        if ($isFresh) {
            // Step 5a: Full seed on fresh install
            $this->step('db:seed (full)', function () {
                Artisan::call('db:seed', ['--force' => true]);
            });
        } else {
            // Step 5b: Re-seed only permissions on update (non-destructive)
            $this->step('db:seed (PermissionsTableSeeder)', function () {
                Artisan::call('db:seed', [
                    '--class' => 'PermissionsTableSeeder',
                    '--force' => true,
                ]);
            });
        }

        // Step 6: Ensure OAuth clients exist
        $this->step('passport:install', function () {
            Artisan::call('passport:install', ['--force' => true]);
        });

        // Step 7: Reset Spatie permission cache
        $this->step('permission:cache-reset', function () {
            Artisan::call('permission:cache-reset');
        });

        // Step 8: Cache config/routes — graceful fallback if config has Closures
        $this->step('cache (config/routes)', function () {
            // config:cache — throws LogicException if any config file contains a Closure
            try {
                Artisan::call('config:cache');
                $this->line('    config:cache  ✓');
            } catch (\LogicException $e) {
                // Non-serializable config (e.g. Closures in a custom config file)
                Artisan::call('config:clear');
                $this->warn('    config:cache skipped — config contains non-serializable values.');
                $this->line('    config:clear  ✓ (applied as fallback)');
            } catch (\Throwable $e) {
                Artisan::call('config:clear');
                $this->warn('    config:cache skipped (' . $e->getMessage() . ')');
                $this->line('    config:clear  ✓ (applied as fallback)');
            }

            // route:cache — safe on nearly all installations
            try {
                Artisan::call('route:cache');
                $this->line('    route:cache   ✓');
            } catch (\Throwable $e) {
                Artisan::call('route:clear');
                $this->warn('    route:cache skipped (' . $e->getMessage() . ')');
                $this->line('    route:clear   ✓ (applied as fallback)');
            }

            // view:cache — compile Blade templates up front (best-effort)
            try {
                Artisan::call('view:cache');
                $this->line('    view:cache    ✓');
            } catch (\Throwable $e) {
                Artisan::call('view:clear');
                $this->warn('    view:cache skipped (' . $e->getMessage() . ')');
            }
        });

        // Stamp installed version so the in-app update banner resolves correctly
        $this->step('stamp app_version', function () {
            try {
                $version = function_exists('pos_release_version') ? pos_release_version() : config('author.app_version', '0');
                System::updateOrCreate(
                    ['key' => 'app_version'],
                    ['value' => $version]
                );
            } catch (\Throwable $e) {
                // Non-fatal — may fail on a brand-new DB before migrations run
            }
        });

        // On the central update server, auto-package the just-deployed code so
        // release manifest/version stay in sync without manual packaging.
        $this->step('auto-package release (central server)', function () {
            try {
                // Clients point to a remote update server; central server typically does not.
                $updateServerUrl = trim((string) env('UPDATE_SERVER_URL', ''));
                if ($updateServerUrl !== '') {
                    $this->line('    skipped (client server)');
                    return;
                }

                $version = function_exists('pos_release_version') ? pos_release_version() : config('author.app_version', '0');

                Artisan::call('pos:package-release', [
                    '--pkg-version' => $version,
                    '--force' => true,
                ]);

                $this->line('    packaged release v' . $version . ' and refreshed manifest.json');
            } catch (\Throwable $e) {
                $this->warn('    auto-package skipped (' . $e->getMessage() . ')');
            }
        });

        $this->info('');
        $this->info('==> pos:deploy complete.');

        if ($isFresh) {
            $this->newLine();
            $this->warn('Next step: open the application in a browser and register your business.');
            $this->warn('Business registration creates the Admin and Cashier roles automatically.');
        }

        return self::SUCCESS;
    }

    private function step(string $label, callable $fn): void
    {
        $this->line("  --> {$label}");
        $fn();
    }
}
