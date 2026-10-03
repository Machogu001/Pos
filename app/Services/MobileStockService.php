<?php

namespace App\Services;

use App\VariationLocationDetails;
use App\Utils\ProductUtil;

class MobileStockService
{
    public function __construct(protected ProductUtil $productUtil)
    {
    }

    public function comboAvailability(int $locationId, array $variations): ?float
    {
        $available = null;
        foreach ($this->productUtil->calculateComboDetails($locationId, $variations) as $component) {
            if (! $component['enable_stock']) {
                continue;
            }
            $stock = (float) VariationLocationDetails::where('variation_id', $component['variation_id'])
                ->where('location_id', $locationId)->value('qty_available');
            $required = (float) $component['qty_required'];
            if ($required <= 0) {
                throw new \InvalidArgumentException('Invalid stock quantity configured for a combo product.');
            }
            $units = floor(max(0, $stock) / $required);
            $available = $available === null ? $units : min($available, $units);
        }

        return $available;
    }
}
