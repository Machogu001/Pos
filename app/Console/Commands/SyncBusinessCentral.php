<?php

namespace App\Console\Commands;

use App\Services\BusinessCentralSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncBusinessCentral extends Command
{
    protected $signature = 'business-central:sync';

    protected $description = 'Synchronize BC products, customers, inventory, and pending POS sales';

    public function handle(BusinessCentralSyncService $service): int
    {
        try {
            $result = $service->syncAll();
            if (! $result['enabled']) {
                $this->info('Business Central integration is disabled.');

                return self::SUCCESS;
            }

            $this->info(sprintf(
                'Synchronized %d items, %d customers, %d inventory rows, and %d sales.',
                $result['items'],
                $result['customers'],
                $result['inventory'],
                $result['sales']
            ));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
