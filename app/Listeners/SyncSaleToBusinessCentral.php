<?php

namespace App\Listeners;

use App\Events\SellCreatedOrModified;
use App\Services\BusinessCentralSyncService;

class SyncSaleToBusinessCentral
{
    public function __construct(protected BusinessCentralSyncService $service)
    {
    }

    public function handle(SellCreatedOrModified $event): void
    {
        $this->service->queueSale($event->transaction);
    }
}
