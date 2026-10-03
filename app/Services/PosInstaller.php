<?php

namespace App\Services;

use App\System;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class PosInstaller
{
    public function validateEnvironmentConfig(): array
    {
        $requirements = [
            'APP_NAME' => trim((string) env('APP_NAME', '')),
            'ENVATO_PURCHASE_CODE' => trim((string) env('ENVATO_PURCHASE_CODE', '')),
            'DB_HOST' => trim((string) env('DB_HOST', '')),
            'DB_PORT' => trim((string) env('DB_PORT', '')),
            'DB_DATABASE' => trim((string) env('DB_DATABASE', '')),
            'DB_USERNAME' => trim((string) env('DB_USERNAME', '')),
        ];

        $missing = [];
        foreach ($requirements as $key => $value) {
            if ($value === '') {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    public function run(bool $fresh = false, ?callable $logger = null): void
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        $this->prepareInstallEnvironment($logger);

        $this->runArtisanStep('pos:setup', ['--force' => true], true, $logger);

        try {
            DB::statement('SET default_storage_engine=INNODB;');
        } catch (\Throwable $e) {
            $this->emit($logger, 'warn', 'Installer could not set default_storage_engine; continuing.');
            \Log::warning('PosInstaller: unable to set default_storage_engine', [
                'message' => $e->getMessage(),
            ]);
        }

        $this->runArtisanStep($fresh ? 'migrate:fresh' : 'migrate', ['--force' => true], true, $logger);
        $this->runArtisanStep('module:migrate', ['--force' => true], true, $logger);
        $this->runArtisanStep('module:publish', [], false, $logger);
        $this->runArtisanStep('db:seed', ['--force' => true], true, $logger);
        $this->runArtisanStep('passport:install', ['--force' => true], true, $logger);
        $this->runArtisanStep('permission:cache-reset', [], false, $logger);
        $this->runArtisanStep('package:discover', ['--ansi' => false], true, $logger);
        $this->runArtisanStep('optimize', [], false, $logger);
        $this->stampInstalledVersion();
    }

    private function prepareInstallEnvironment(?callable $logger = null): void
    {
        app(RuntimePermissions::class)->repair(base_path(), env('POS_WEB_USER'));
        foreach (['packages.php', 'services.php'] as $cacheFile) {
            $path = base_path('bootstrap/cache/' . $cacheFile);
            if (file_exists($path)) {
                @unlink($path);
            }
        }

        Artisan::call('config:clear');
        Artisan::call('cache:clear');

        $this->emit($logger, 'line', 'Installer cache state cleared.');
    }

    private function runArtisanStep(string $command, array $parameters = [], bool $critical = true, ?callable $logger = null): int
    {
        if ($command === 'permission:cache-reset') {
            try {
                app(PermissionRegistrar::class)->forgetCachedPermissions();
                $this->emit($logger, 'line', 'permission:cache-reset completed.');

                return 0;
            } catch (\Throwable $e) {
                if ($critical) {
                    throw new \RuntimeException($e->getMessage(), 0, $e);
                }

                $this->emit($logger, 'warn', 'permission:cache-reset skipped: ' . $e->getMessage());
                \Log::warning('Non-critical install command failed', [
                    'command' => $command,
                    'exit_code' => 1,
                    'output' => $e->getMessage(),
                ]);

                return 1;
            }
        }

        $exitCode = Artisan::call($command, $parameters);
        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            $message = $output !== '' ? $output : "Command {$command} failed with exit code {$exitCode}.";

            if ($critical) {
                throw new \RuntimeException($message);
            }

            $this->emit($logger, 'warn', $message);
            \Log::warning('Non-critical install command failed', [
                'command' => $command,
                'exit_code' => $exitCode,
                'output' => $output,
            ]);

            return $exitCode;
        }

        $this->emit($logger, 'line', $command . ' completed.');

        return 0;
    }

    private function stampInstalledVersion(): void
    {
        try {
            $version = function_exists('pos_release_version') ? pos_release_version() : config('author.app_version', '0');

            System::updateOrCreate(
                ['key' => 'app_version'],
                ['value' => $version]
            );
        } catch (\Throwable $e) {
            \Log::warning('PosInstaller: unable to stamp installed version', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function emit(?callable $logger, string $level, string $message): void
    {
        if ($logger !== null) {
            $logger($level, $message);
        }
    }
}