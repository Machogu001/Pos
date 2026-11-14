<?php

namespace App\Listeners;

use App\Events\SellCreatedOrModified;
use App\Services\BusinessCentralSyncService;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncSaleToBusinessCentral
{
    public function __construct(protected BusinessCentralSyncService $service)
    {
    }

    public function handle(SellCreatedOrModified $event): array
    {
        $businessId = (int) $event->transaction->business_id;
        if (! $this->service->enabledFor($businessId)) {
            return [
                'integration' => 'business_central',
                'enabled' => false,
                'success' => null,
            ];
        }

        try {
            $this->service->syncSale($event->transaction);

            return [
                'integration' => 'business_central',
                'enabled' => true,
                'success' => true,
            ];
        } catch (Throwable $e) {
            $businessCentralError = mb_substr(trim(strip_tags($e->getMessage())), 0, 500);
            Log::warning('Immediate Business Central sale sync failed; the scheduled sync will retry it.', [
                'transaction_id' => $event->transaction->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'integration' => 'business_central',
                'enabled' => true,
                'success' => false,
                'error' => $businessCentralError,
                'message' => 'Sale saved in POS, but Business Central sync failed: '
                    .$businessCentralError
                    .'. The sale will retry automatically.',
            ];
        }
    }
}
