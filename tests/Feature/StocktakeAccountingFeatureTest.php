<?php

namespace Tests\Feature;

use App\AccountTransaction;
use App\Events\StockAdjustmentCreatedOrModified;
use App\Http\Controllers\StocktakeController;
use App\Listeners\SyncStockAdjustmentAccountTransaction;
use App\Product;
use App\ProductVariation;
use App\Stocktake;
use App\StocktakeItem;
use App\Transaction;
use App\User;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StocktakeAccountingFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('business', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->json('enabled_modules')->nullable();
            $table->json('common_settings')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->string('surname')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->boolean('allow_login')->default(true);
            $table->string('status')->default('active');
            $table->string('user_type')->default('user');
            $table->boolean('otp_login_enabled')->default(false);
            $table->unsignedBigInteger('preferred_location_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('business_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->boolean('enable_stock')->default(true);
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('actual_name')->nullable();
            $table->string('short_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->timestamps();
        });

        Schema::create('variations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variation_id');
            $table->string('name')->default('DUMMY');
            $table->string('sub_sku')->nullable();
            $table->decimal('dpp_inc_tax', 22, 4)->default(0);
            $table->decimal('default_purchase_price', 22, 4)->default(0);
            $table->decimal('default_sell_price', 22, 4)->default(0);
            $table->decimal('sell_price_inc_tax', 22, 4)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stocktakes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id');
            $table->string('reference_no');
            $table->string('status')->default('in_progress');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->unsignedBigInteger('adjustment_transaction_id')->nullable();
            $table->text('additional_notes')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stocktake_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stocktake_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variation_id');
            $table->unsignedBigInteger('variation_id');
            $table->decimal('system_quantity', 22, 4)->default(0);
            $table->decimal('counted_quantity', 22, 4)->default(0);
            $table->decimal('variance', 22, 4)->default(0);
            $table->string('lot_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->unsignedBigInteger('business_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->string('status')->nullable();
            $table->string('payment_status')->nullable();
            $table->boolean('is_stocktake')->default(false);
            $table->decimal('final_total', 22, 4)->default(0);
            $table->decimal('total_before_tax', 22, 4)->default(0);
            $table->string('ref_no')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('additional_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_adjustment_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variation_id');
            $table->decimal('quantity', 22, 4)->default(0);
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->unsignedBigInteger('removed_purchase_line')->nullable();
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->string('name');
            $table->string('account_number')->nullable();
            $table->unsignedBigInteger('account_type_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 22, 4);
            $table->unsignedBigInteger('account_id');
            $table->string('type');
            $table->string('sub_type')->nullable();
            $table->string('reff_no')->nullable();
            $table->timestamp('operation_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('transaction_payment_id')->nullable();
            $table->unsignedBigInteger('transfer_transaction_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });
    }

    public function test_stocktake_completion_creates_signed_adjustment_transaction_and_marks_stocktake_completed()
    {
        Gate::define('stocktake.complete', fn () => true);

        DB::table('business')->insert([
            'id' => 1,
            'name' => 'Test Business',
            'enabled_modules' => json_encode(['account']),
            'common_settings' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('business_locations')->insert([
            'id' => 1,
            'business_id' => 1,
            'name' => 'Main Branch',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::create([
            'business_id' => 1,
            'surname' => 'Tester',
            'first_name' => 'Stock',
            'last_name' => 'User',
            'username' => 'stock-user',
            'status' => 'active',
            'allow_login' => 1,
        ]);

        $this->actingAs($user);

        $product = Product::create([
            'id' => 1,
            'business_id' => 1,
            'unit_id' => 1,
            'name' => 'Widget',
            'sku' => 'W-1',
            'enable_stock' => 1,
        ]);

        DB::table('units')->insert([
            'id' => 1,
            'actual_name' => 'Pieces',
            'short_name' => 'pcs',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productVariation = ProductVariation::create([
            'id' => 1,
            'product_id' => $product->id,
        ]);

        Variation::create([
            'id' => 1,
            'product_id' => $product->id,
            'product_variation_id' => $productVariation->id,
            'name' => 'DUMMY',
            'sub_sku' => 'W-1-D',
            'dpp_inc_tax' => 2.50,
            'default_purchase_price' => 2.50,
            'default_sell_price' => 4.00,
            'sell_price_inc_tax' => 4.50,
        ]);

        $stocktake = Stocktake::create([
            'business_id' => 1,
            'location_id' => 1,
            'reference_no' => 'ST-TEST-001',
            'status' => 'in_progress',
            'created_by' => $user->id,
            'transaction_date' => now(),
            'started_at' => now(),
        ]);

        StocktakeItem::create([
            'stocktake_id' => $stocktake->id,
            'product_id' => $product->id,
            'product_variation_id' => $productVariation->id,
            'variation_id' => 1,
            'system_quantity' => 10,
            'counted_quantity' => 7,
            'variance' => -3,
        ]);

        $productUtil = \Mockery::mock(ProductUtil::class);
        $productUtil->shouldReceive('validateStocktakeData')->once()->andReturn([]);
        $productUtil->shouldReceive('getStockByVariation')->once()->with(1, 1, null, null)->andReturn(10.0);
        $productUtil->shouldReceive('addStockHistory')->once()->andReturn(true);
        $productUtil->shouldReceive('updateProductQuantityForStocktake')->once()->andReturn(true);

        $transactionUtil = \Mockery::mock(TransactionUtil::class);

        $this->app->instance(ProductUtil::class, $productUtil);
        $this->app->instance(TransactionUtil::class, $transactionUtil);

        Event::fake([StockAdjustmentCreatedOrModified::class]);

        $response = $this->app->make(StocktakeController::class)->complete($stocktake->id);

        $responseData = $response->getData(true);

        $this->assertTrue($responseData['success'], $responseData['msg'] ?? 'Stocktake completion did not return success.');
        $this->assertSame(route('stocktakes.show', $stocktake->id), $responseData['redirect']);

        $stocktake->refresh();
        $adjustmentTransaction = Transaction::findOrFail($stocktake->adjustment_transaction_id);
        $adjustmentLine = DB::table('stock_adjustment_lines')->where('transaction_id', $adjustmentTransaction->id)->first();

        $this->assertSame('completed', $stocktake->status);
        $this->assertNotNull($stocktake->completed_at);
        $this->assertEquals(-7.50, (float) $adjustmentTransaction->final_total);
        $this->assertEquals(-7.50, (float) $adjustmentTransaction->total_before_tax);
        $this->assertEquals(-3.0, (float) $adjustmentLine->quantity);
        $this->assertEquals(2.50, (float) $adjustmentLine->unit_price);

        Event::assertDispatched(StockAdjustmentCreatedOrModified::class, function ($event) use ($adjustmentTransaction) {
            return $event->stockAdjustment->id === $adjustmentTransaction->id && $event->action === 'added';
        });
    }

    public function test_stocktake_gain_posts_inventory_and_gain_entries()
    {
        DB::table('business')->insert([
            'id' => 1,
            'name' => 'Test Business',
            'enabled_modules' => json_encode(['account']),
            'common_settings' => json_encode([
                'default_account_mappings' => [
                    'inventory' => 101,
                    'inventory_gain' => 102,
                    'inventory_loss' => 103,
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('accounts')->insert([
            ['id' => 101, 'business_id' => 1, 'name' => 'Inventory', 'account_number' => '1400', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 102, 'business_id' => 1, 'name' => 'Inventory Gain', 'account_number' => '4100', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 103, 'business_id' => 1, 'name' => 'Inventory Loss', 'account_number' => '6100', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('variations')->insert([
            'id' => 1,
            'product_id' => 1,
            'product_variation_id' => 1,
            'name' => 'DUMMY',
            'dpp_inc_tax' => 3.00,
            'default_purchase_price' => 3.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transactions')->insert([
            'id' => 55,
            'type' => 'stock_adjustment',
            'business_id' => 1,
            'location_id' => 1,
            'transaction_date' => now(),
            'status' => 'received',
            'payment_status' => 'paid',
            'is_stocktake' => 1,
            'final_total' => 12.00,
            'total_before_tax' => 12.00,
            'ref_no' => 'SA-TEST-001',
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('stock_adjustment_lines')->insert([
            'transaction_id' => 55,
            'product_id' => 1,
            'variation_id' => 1,
            'quantity' => 4,
            'unit_price' => 3.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $moduleUtil = \Mockery::mock(ModuleUtil::class);
        $moduleUtil->shouldReceive('isModuleEnabled')->once()->with('account', 1)->andReturn(true);

        $listener = new SyncStockAdjustmentAccountTransaction($moduleUtil);
        $listener->handle(new StockAdjustmentCreatedOrModified(Transaction::findOrFail(55), 'added'));

        $entries = AccountTransaction::where('transaction_id', 55)->orderBy('reff_no')->get()->keyBy('reff_no');

        $this->assertCount(2, $entries);
        $this->assertEquals('debit', $entries['stock_adjustment_inventory']->type);
        $this->assertEquals(12.00, (float) $entries['stock_adjustment_inventory']->amount);
        $this->assertEquals(101, (int) $entries['stock_adjustment_inventory']->account_id);
        $this->assertEquals('credit', $entries['stock_adjustment_inventory_gain']->type);
        $this->assertEquals(12.00, (float) $entries['stock_adjustment_inventory_gain']->amount);
        $this->assertEquals(102, (int) $entries['stock_adjustment_inventory_gain']->account_id);
    }

    public function test_baseline_stocktake_posts_to_opening_stock_equity_before_cutoff()
    {
        DB::table('business')->insert([
            'id' => 1,
            'name' => 'Test Business',
            'enabled_modules' => json_encode(['account']),
            'common_settings' => json_encode([
                'stocktake_opening_balance_cutoff_date' => '2026-12-31',
                'default_account_mappings' => [
                    'inventory' => 101,
                    'opening_stock_equity' => 104,
                    'inventory_gain' => 102,
                    'inventory_loss' => 103,
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('accounts')->insert([
            ['id' => 101, 'business_id' => 1, 'name' => 'Inventory', 'account_number' => '1400', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 102, 'business_id' => 1, 'name' => 'Inventory Gain', 'account_number' => '4100', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 103, 'business_id' => 1, 'name' => 'Inventory Loss', 'account_number' => '6100', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 104, 'business_id' => 1, 'name' => 'Opening Stock Equity', 'account_number' => '3100', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('variations')->insert([
            'id' => 1,
            'product_id' => 1,
            'product_variation_id' => 1,
            'name' => 'DUMMY',
            'dpp_inc_tax' => 5.00,
            'default_purchase_price' => 5.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transactions')->insert([
            'id' => 77,
            'type' => 'stock_adjustment',
            'business_id' => 1,
            'location_id' => 1,
            'transaction_date' => '2026-01-05 09:00:00',
            'status' => 'received',
            'payment_status' => 'paid',
            'is_stocktake' => 1,
            'final_total' => 10.00,
            'total_before_tax' => 10.00,
            'ref_no' => 'SA-OPEN-001',
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('stock_adjustment_lines')->insert([
            'transaction_id' => 77,
            'product_id' => 1,
            'variation_id' => 1,
            'quantity' => 2,
            'unit_price' => 5.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $moduleUtil = \Mockery::mock(ModuleUtil::class);
        $moduleUtil->shouldReceive('isModuleEnabled')->once()->with('account', 1)->andReturn(true);

        $listener = new SyncStockAdjustmentAccountTransaction($moduleUtil);
        $listener->handle(new StockAdjustmentCreatedOrModified(Transaction::findOrFail(77), 'added'));

        $entries = AccountTransaction::where('transaction_id', 77)->orderBy('reff_no')->get()->keyBy('reff_no');

        $this->assertCount(2, $entries);
        $this->assertArrayHasKey('stock_adjustment_inventory', $entries->toArray());
        $this->assertArrayHasKey('stock_adjustment_inventory_opening_equity', $entries->toArray());
        $this->assertArrayNotHasKey('stock_adjustment_inventory_gain', $entries->toArray());
        $this->assertArrayNotHasKey('stock_adjustment_inventory_loss', $entries->toArray());
        $this->assertEquals('credit', $entries['stock_adjustment_inventory_opening_equity']->type);
        $this->assertEquals(10.00, (float) $entries['stock_adjustment_inventory_opening_equity']->amount);
        $this->assertEquals(104, (int) $entries['stock_adjustment_inventory_opening_equity']->account_id);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('account_transactions');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('stock_adjustment_lines');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('stocktake_items');
        Schema::dropIfExists('stocktake');
        Schema::dropIfExists('stocktakes');
        Schema::dropIfExists('variations');
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('units');
        Schema::dropIfExists('products');
        Schema::dropIfExists('business_locations');
        Schema::dropIfExists('users');
        Schema::dropIfExists('business');
        if (Schema::hasTable('migrations')) {
            \DB::table('migrations')->truncate();
        }
        parent::tearDown();
    }
}