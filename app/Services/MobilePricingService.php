<?php

namespace App\Services;

use App\Business;
use App\BusinessLocation;
use App\TaxRate;
use App\Utils\ContactUtil;
use App\Utils\ProductUtil;

/**
 * Resolves the selling price of a variation exactly like the web POS product row does
 * (location/customer selling price group, customer group percentage, active product
 * discount, inline tax setting) so mobile totals always match the server-side sale.
 */
class MobilePricingService
{
    public function __construct(
        protected ProductUtil $productUtil,
        protected ContactUtil $contactUtil
    ) {
    }

    public function priceGroupFor(int $businessId, BusinessLocation $location, ?int $contactId): ?int
    {
        $customerGroup = $this->contactUtil->getCustomerGroup($businessId, $contactId);
        if (! empty($customerGroup)
            && ($customerGroup->price_calculation_type ?? null) === 'selling_price_group'
            && ! empty($customerGroup->selling_price_group_id)) {
            return (int) $customerGroup->selling_price_group_id;
        }

        return ! empty($location->selling_price_group_id) ? (int) $location->selling_price_group_id : null;
    }

    /**
     * @param  object  $product  Row from ProductUtil::getDetailsFromVariation().
     * @return array{unit_price: float, unit_price_inc_tax: float, item_tax: float, tax_id: ?int,
     *     line_discount_type: string, line_discount_amount: float, discount_id: ?int,
     *     price_before_discount_inc_tax: float, tax_rate: float}
     */
    public function priceLine(object $product, Business $business, BusinessLocation $location, ?int $contactId): array
    {
        $businessId = (int) $business->id;
        $variationId = (int) $product->variation_id;
        $defaultSellPrice = (float) $product->default_sell_price;
        $sellPriceIncTax = (float) $product->sell_price_inc_tax;

        $customerGroup = $this->contactUtil->getCustomerGroup($businessId, $contactId);
        $percent = (empty($customerGroup) || empty($customerGroup->amount)
            || ($customerGroup->price_calculation_type ?? null) !== 'percentage') ? 0 : (float) $customerGroup->amount;
        $defaultSellPrice += $percent * $defaultSellPrice / 100;
        $sellPriceIncTax += $percent * $sellPriceIncTax / 100;

        $priceGroup = $this->priceGroupFor($businessId, $location, $contactId);
        if (! empty($priceGroup)) {
            $groupPrices = $this->productUtil->getVariationGroupPrice($variationId, $priceGroup, $product->tax_id);
            if (! empty($groupPrices['price_inc_tax'])) {
                $sellPriceIncTax = (float) $groupPrices['price_inc_tax'];
                $defaultSellPrice = (float) $groupPrices['price_exc_tax'];
            }
        }

        $discount = $this->productUtil->getProductDiscount(
            $product,
            $businessId,
            (int) $location->id,
            ! empty($customerGroup) && ! empty($customerGroup->id),
            $priceGroup,
            $variationId
        );
        $discountType = ! empty($discount) ? $discount->discount_type : 'fixed';
        $discountAmount = ! empty($discount) ? (float) $discount->discount_amount : 0.0;

        $inlineTax = (int) ($business->enable_inline_tax ?? 0) === 1;
        $taxId = $inlineTax && ! empty($product->tax_id) ? (int) $product->tax_id : null;
        $taxRate = $taxId ? (float) (TaxRate::where('id', $taxId)->value('amount') ?? 0) : 0.0;

        $discountedExcTax = $this->applyDiscount($defaultSellPrice, $discountType, $discountAmount);
        $unitPriceIncTax = $discountedExcTax * (1 + $taxRate / 100);

        return [
            'unit_price' => round($defaultSellPrice, 4),
            'unit_price_inc_tax' => round($unitPriceIncTax, 4),
            'item_tax' => round($unitPriceIncTax - $discountedExcTax, 4),
            'tax_id' => $taxId,
            'tax_rate' => $taxRate,
            'line_discount_type' => $discountType,
            'line_discount_amount' => round($discountAmount, 4),
            'discount_id' => ! empty($discount) ? (int) $discount->id : null,
            'price_before_discount_inc_tax' => round($inlineTax ? $sellPriceIncTax : $defaultSellPrice, 4),
        ];
    }

    /**
     * Price line for a client-supplied price (users allowed to edit the POS price).
     * The edited price replaces the discounted inc-tax price, mirroring the POS screen.
     */
    public function overridePrice(array $line, float $unitPriceIncTax): array
    {
        $rate = (float) $line['tax_rate'];
        $excTax = $rate > 0 ? $unitPriceIncTax / (1 + $rate / 100) : $unitPriceIncTax;

        return array_merge($line, [
            'unit_price' => round($excTax, 4),
            'unit_price_inc_tax' => round($unitPriceIncTax, 4),
            'item_tax' => round($unitPriceIncTax - $excTax, 4),
            'line_discount_type' => 'fixed',
            'line_discount_amount' => 0.0,
            'discount_id' => null,
        ]);
    }

    protected function applyDiscount(float $price, string $type, float $amount): float
    {
        if ($amount <= 0) {
            return $price;
        }

        $discounted = $type === 'percentage' ? $price * (100 - $amount) / 100 : $price - $amount;

        return max($discounted, 0.0);
    }
}
