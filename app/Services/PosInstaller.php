<?php

namespace App\Services;

use App\Account;
use App\Business;
use App\System;
use App\Utils\BusinessUtil;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        $this->seedBundledModules($logger);
        $this->runArtisanStep('module:publish', [], false, $logger);
        $this->runArtisanStep('db:seed', ['--force' => true], true, $logger);
        $this->bootstrapBusinesses($logger);
        $this->stampBundledModuleVersions($logger);
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

    private function bootstrapBusinesses(?callable $logger = null): void
    {
        if (! Schema::hasTable('business')) {
            return;
        }

        $businessUtil = app(BusinessUtil::class);

        Business::select('id', 'owner_id', 'common_settings')
            ->orderBy('id')
            ->chunkById(50, function ($businesses) use ($businessUtil) {
                foreach ($businesses as $business) {
                    $ownerId = $business->owner_id ?: 1;

                    $businessUtil->provisionDefaultAccountMappings($business->id, $ownerId);
                    $this->ensureMappedAccountsExistAsChartAccounts($business->id);
                }
            });

        $this->emit($logger, 'line', 'Business bootstrap completed.');
    }

    private function seedBundledModules(?callable $logger = null): void
    {
        foreach (glob(base_path('Modules/*/Database/Seeders/*DatabaseSeeder.php')) ?: [] as $seederPath) {
            $relative = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $seederPath);
            $class = str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relative);
            $this->runArtisanStep('db:seed', ['--class' => $class, '--force' => true], true, $logger);
        }

        $this->emit($logger, 'line', 'Bundled module seeders completed.');
    }

    private function stampBundledModuleVersions(?callable $logger = null): void
    {
        foreach (['Hrm', 'Stocktake'] as $moduleName) {
            $propertyKey = strtolower($moduleName) . '_version';
            if (! empty(System::getProperty($propertyKey))) {
                continue;
            }

            $moduleJsonPath = base_path('Modules/' . $moduleName . '/module.json');
            $version = '1.0.0';

            if (is_file($moduleJsonPath)) {
                $decoded = json_decode((string) file_get_contents($moduleJsonPath), true);
                if (is_array($decoded) && ! empty($decoded['version'])) {
                    $version = (string) $decoded['version'];
                }
            }

            System::updateOrCreate(
                ['key' => $propertyKey],
                ['value' => $version]
            );
        }

        $this->emit($logger, 'line', 'Bundled module versions stamped.');
    }

    private function ensureMappedAccountsExistAsChartAccounts(int $businessId): void
    {
        $chartModel = '\\Modules\\Accounting\\Entities\\ChartOfAccount';

        if (! class_exists($chartModel)
            || ! Schema::hasTable('chart_of_accounts')
            || ! Schema::hasTable('accounts')) {
            return;
        }

        $business = Business::select('id', 'currency_id', 'common_settings')->find($businessId);
        if (empty($business)) {
            return;
        }

        $typeMappings = ! empty($business->common_settings['default_account_mappings'])
            && is_array($business->common_settings['default_account_mappings'])
            ? $business->common_settings['default_account_mappings']
            : [];

        if (empty($typeMappings)) {
            return;
        }

        foreach ($typeMappings as $accountId) {
            if (empty($accountId)) {
                continue;
            }

            $account = Account::with('account_type.parent_account')
                ->where('business_id', $business->id)
                ->find($accountId);

            if (empty($account)) {
                continue;
            }

            $glCode = (int) $account->account_number;
            if ($glCode <= 0) {
                continue;
            }

            $existingChart = $chartModel::where('business_id', $business->id)
                ->where('gl_code', $glCode)
                ->first();

            if (! empty($existingChart)) {
                continue;
            }

            $chartModel::create([
                'business_id' => $business->id,
                'name' => $account->name,
                'gl_code' => $glCode,
                'account_type' => optional(optional($account->account_type)->parent_account)->name ?: optional($account->account_type)->name,
                'account_sub_type' => optional($account->account_type)->name,
                'detail_type' => optional($account->account_type)->name,
                'status' => 'active',
                'note' => 'Auto-created during installation bootstrap from default account mappings.',
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