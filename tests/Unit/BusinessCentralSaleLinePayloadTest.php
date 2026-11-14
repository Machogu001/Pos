<?php

namespace Tests\Unit;

use App\Product;
use App\Services\BusinessCentralSyncService;
use App\TransactionSellLine;
use App\Unit;
use App\Utils\ProductUtil;
use App\Variation;
use Mockery;
use Tests\TestCase;

class BusinessCentralSaleLinePayloadTest extends TestCase
{
    public function test_it_restores_the_selected_unit_quantity_and_price_for_bc(): void
    {
        $productUtil = Mockery::mock(ProductUtil::class);
        $productUtil->shouldReceive('getProductUnitMultiplier')
            ->once()
            ->with(1, 10, 2, 1)
            ->andReturn(100);

        $baseUnit = new Unit(['short_name' => 'PCS']);
        $baseUnit->id = 1;
        $wholesaleUnit = new Unit(['short_name' => 'WHL']);
        $wholesaleUnit->id = 2;
        $product = new Product(['unit_id' => 1, 'sku' => 'ITM00038']);
        $product->id = 10;
        $product->setRelation('unit', $baseUnit);
        $variation = new Variation(['sub_sku' => 'ITM00038']);
        $line = new TransactionSellLine([
            'product_id' => 10,
            'sub_unit_id' => 2,
            'quantity' => 100,
            'unit_price_before_discount' => 6.05,
        ]);
        $line->setRelation('product', $product);
        $line->setRelation('sub_unit', $wholesaleUnit);
        $line->setRelation('variations', $variation);

        $payload = (new TestableBusinessCentralSyncService($productUtil))->linePayload($line, 1);

        $this->assertSame('WHL', $payload['unitOfMeasureCode']);
        $this->assertSame(1.0, $payload['quantity']);
        $this->assertSame(605.0, $payload['unitPrice']);
    }
}

class TestableBusinessCentralSyncService extends BusinessCentralSyncService
{
    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
    }

    public function linePayload(TransactionSellLine $line, int $businessId): array
    {
        return $this->saleLinePayload($line, $businessId);
    }
}
