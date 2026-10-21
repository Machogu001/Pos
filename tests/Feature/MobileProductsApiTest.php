<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\ProductController;
use App\Services\MobilePricingService;
use App\Utils\ProductUtil;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileProductsApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('product_locations');
        Schema::dropIfExists('variation_location_details');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('units');
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('variations');
        Schema::dropIfExists('products');

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('type')->default('single');
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->unsignedBigInteger('tax')->nullable();
            $table->boolean('not_for_selling')->default(false);
            $table->boolean('enable_stock')->default(true);
            $table->string('image')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_dummy')->default(true);
            $table->timestamps();
        });

        Schema::create('variations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variation_id');
            $table->string('name')->default('DUMMY');
            $table->string('sub_sku')->nullable();
            $table->decimal('default_sell_price', 22, 4)->default(0);
            $table->decimal('sell_price_inc_tax', 22, 4)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('short_name')->nullable();
        });

        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 22, 4)->default(0);
        });

        Schema::create('variation_location_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('variation_id');
            $table->unsignedBigInteger('location_id');
            $table->decimal('qty_available', 22, 4)->nullable();
        });

        Schema::create('product_locations', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('location_id');
        });

        $this->seedProducts();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('product_locations');
        Schema::dropIfExists('variation_location_details');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('units');
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('variations');
        Schema::dropIfExists('products');
        parent::tearDown();
    }

    public function test_products_can_sort_by_name()
    {
        $names = $this->queryProductNames('product_name');

        $this->assertSame(['Alpha', 'Bravo', 'Zulu'], $names);
    }

    public function test_products_can_sort_by_qty_available_descending_then_name()
    {
        $names = $this->queryProductNames('qty_available');

        $this->assertSame(['Bravo', 'Zulu', 'Alpha'], $names);
    }

    private function queryProductNames(string $sortBy): array
    {
        $controller = new TestableProductController(
            \Mockery::mock(ProductUtil::class),
            \Mockery::mock(MobilePricingService::class)
        );

        /** @var Builder $query */
        $query = $controller->queryProductsPublic(1, 1, null, false, $sortBy);

        return $query->get()->pluck('product_name')->all();
    }

    private function seedProducts(): void
    {
        \DB::table('product_variations')->insert([
            ['id' => 1, 'name' => 'DUMMY', 'is_dummy' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'DUMMY', 'is_dummy' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'DUMMY', 'is_dummy' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        \DB::table('products')->insert([
            ['id' => 1, 'business_id' => 1, 'name' => 'Zulu', 'sku' => 'Z1', 'type' => 'single', 'not_for_selling' => 0, 'enable_stock' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'business_id' => 1, 'name' => 'Alpha', 'sku' => 'A1', 'type' => 'single', 'not_for_selling' => 0, 'enable_stock' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'business_id' => 1, 'name' => 'Bravo', 'sku' => 'B1', 'type' => 'single', 'not_for_selling' => 0, 'enable_stock' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        \DB::table('variations')->insert([
            ['id' => 101, 'product_id' => 1, 'product_variation_id' => 1, 'name' => 'Regular', 'sub_sku' => 'Z1', 'default_sell_price' => 10, 'sell_price_inc_tax' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 102, 'product_id' => 2, 'product_variation_id' => 2, 'name' => 'Regular', 'sub_sku' => 'A1', 'default_sell_price' => 10, 'sell_price_inc_tax' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 103, 'product_id' => 3, 'product_variation_id' => 3, 'name' => 'Regular', 'sub_sku' => 'B1', 'default_sell_price' => 10, 'sell_price_inc_tax' => 10, 'created_at' => now(), 'updated_at' => now()],
        ]);

        \DB::table('variation_location_details')->insert([
            ['variation_id' => 101, 'location_id' => 1, 'qty_available' => 9],
            ['variation_id' => 102, 'location_id' => 1, 'qty_available' => 1],
            ['variation_id' => 103, 'location_id' => 1, 'qty_available' => 20],
        ]);
    }
}

class TestableProductController extends ProductController
{
    public function queryProductsPublic(int $businessId, int $locationId, ?string $term = null, bool $exact = false, string $sortBy = 'product_name'): Builder
    {
        return $this->queryProducts($businessId, $locationId, $term, $exact, $sortBy);
    }
}

