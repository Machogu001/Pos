<?php

namespace App\Console\Commands;

use App\BusinessLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class BackfillStockCostingScope extends Command
{
    protected $signature = 'stock:backfill-costing-scope
                            {--business-id= : Business id, or 0 for all businesses}
                            {--location-id= : Location id, or 0 for all locations}
                            {--dry-run : Preview only, no writes}';

    protected $description = 'Run stock costing layer backfill for the selected business and location scope.';

    public function handle(): int
    {
        $businessId = $this->option('business-id');
        $locationId = $this->option('location-id');
        if ($businessId === null || $locationId === null) {
            $this->error('Options --business-id and --location-id are required; use 0 to select all.');

            return self::FAILURE;
        }

        $businessId = (int) $businessId;
        $locationId = (int) $locationId;
        if ($businessId < 0 || $locationId < 0) {
            $this->error('Business and location ids must be zero or greater.');

            return self::FAILURE;
        }

        $locations = BusinessLocation::query()
            ->when($businessId > 0, fn ($query) => $query->where('business_id', $businessId))
            ->when($locationId > 0, fn ($query) => $query->where('id', $locationId))
            ->orderBy('business_id')
            ->orderBy('id')
            ->get(['id', 'business_id', 'name']);

        if ($locations->isEmpty()) {
            $this->error('No business locations match the selected scope.');

            return self::FAILURE;
        }

        foreach ($locations as $location) {
            $this->info(sprintf(
                'Backfilling business %d, location %d (%s).',
                $location->business_id,
                $location->id,
                $location->name
            ));

            $parameters = [
                '--business-id' => (int) $location->business_id,
                '--location-id' => (int) $location->id,
            ];
            if ((bool) $this->option('dry-run')) {
                $parameters['--dry-run'] = true;
            }

            $exitCode = Artisan::call('stock:backfill-costing-layers', $parameters);
            $this->output->write(Artisan::output());
            if ($exitCode !== self::SUCCESS) {
                return $exitCode;
            }
        }

        return self::SUCCESS;
    }
}