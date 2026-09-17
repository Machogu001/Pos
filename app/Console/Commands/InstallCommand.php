<?php

namespace App\Console\Commands;

use App\Services\PosInstaller;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'pos:install
                            {--fresh : Drop all existing tables before reinstalling}
                            {--force : Skip confirmation prompts}';

    protected $description = 'Run the full POS installation workflow from the command line.';

    public function handle(PosInstaller $installer): int
    {
        if (! file_exists(base_path('.env'))) {
            $this->error('No .env file was found. Copy .env.example to .env, fill in the database settings, then rerun php artisan pos:install --force.');

            return self::FAILURE;
        }

        $missing = $installer->validateEnvironmentConfig();
        if (! empty($missing)) {
            $this->error('The following required .env values are missing: ' . implode(', ', $missing));

            return self::FAILURE;
        }

        $fresh = (bool) $this->option('fresh');
        if ($fresh && ! $this->option('force')) {
            $confirmed = $this->confirm(
                'WARNING: --fresh will drop all database tables and reinstall from scratch. Continue?',
                false
            );

            if (! $confirmed) {
                $this->info('Aborted.');

                return self::FAILURE;
            }
        }

        $this->info('Starting POS installation' . ($fresh ? ' (--fresh)' : '') . '...');

        try {
            $installer->run($fresh, function (string $level, string $message): void {
                if ($level === 'warn') {
                    $this->warn($message);

                    return;
                }

                $this->line($message);
            });
        } catch (\Throwable $e) {
            $this->error('Installation failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('POS installation complete.');
        $this->line('Next step: open the application in the browser and register your business if this is a fresh environment.');

        return self::SUCCESS;
    }
}