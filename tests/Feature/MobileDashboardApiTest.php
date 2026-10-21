<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\DashboardController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileDashboardApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('transaction_payments');
        Schema::dropIfExists('transactions');

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('type');
            $table->string('status')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->dateTime('transaction_date')->nullable();
            $table->decimal('final_total', 22, 4)->default(0);
            $table->string('payment_status')->nullable();
            $table->string('invoice_no')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('transaction_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->decimal('amount', 22, 4)->default(0);
            $table->boolean('is_return')->default(false);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('transaction_payments');
        Schema::dropIfExists('transactions');
        parent::tearDown();
    }

    public function test_dashboard_returns_total_purchase_and_purchase_due_for_selected_date_range()
    {
        $inRange = now()->subDay();
        $outRange = now()->subDays(15);

        $purchaseInRangeId = \DB::table('transactions')->insertGetId([
            'business_id' => 1,
            'type' => 'purchase',
            'status' => 'received',
            'transaction_date' => $inRange,
            'final_total' => 200,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('transaction_payments')->insert([
            'transaction_id' => $purchaseInRangeId,
            'amount' => 50,
            'is_return' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('transactions')->insert([
            'business_id' => 1,
            'type' => 'purchase',
            'status' => 'received',
            'transaction_date' => $outRange,
            'final_total' => 999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/mobile/dashboard', 'GET', [
            'period' => 'custom',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->toDateString(),
        ]);
        $request->setUserResolver(fn () => $this->fakeDashboardUser(1));

        $response = app(DashboardController::class)->show($request);
        $payload = $response->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertEquals(200.0, $payload['data']['total_purchase']);
        $this->assertEquals(150.0, $payload['data']['purchase_due']);
    }

    private function fakeDashboardUser(int $businessId): object
    {
        return new class($businessId) {
            public int $business_id;

            public function __construct(int $businessId)
            {
                $this->business_id = $businessId;
            }

            public function can(string $permission): bool
            {
                return $permission === 'dashboard.data';
            }

            public function permitted_locations(int $businessId = null)
            {
                return 'all';
            }
        };
    }
}

