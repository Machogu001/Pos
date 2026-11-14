<?php

namespace Tests\Unit;

use App\Events\SellCreatedOrModified;
use App\Listeners\SyncSaleToBusinessCentral;
use App\Services\BusinessCentralSyncService;
use App\Transaction;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class SyncSaleToBusinessCentralTest extends TestCase
{
    public function test_it_synchronizes_a_finalized_sale_immediately(): void
    {
        $transaction = new Transaction(['status' => 'final', 'type' => 'sell']);
        $transaction->id = 15;
        $service = $this->mock(BusinessCentralSyncService::class);
        $service->shouldReceive('enabledFor')->once()->with(1)->andReturnTrue();
        $service->shouldReceive('syncSale')->once()->with($transaction)->andReturnTrue();
        $transaction->business_id = 1;

        $result = (new SyncSaleToBusinessCentral($service))->handle(new SellCreatedOrModified($transaction));

        $this->assertSame([
            'integration' => 'business_central',
            'enabled' => true,
            'success' => true,
        ], $result);
    }

    public function test_a_bc_failure_does_not_fail_the_finalized_pos_sale(): void
    {
        $transaction = new Transaction(['status' => 'final', 'type' => 'sell']);
        $transaction->id = 15;
        $transaction->business_id = 1;
        $service = $this->mock(BusinessCentralSyncService::class);
        $service->shouldReceive('enabledFor')->once()->with(1)->andReturnTrue();
        $service->shouldReceive('syncSale')
            ->once()
            ->with($transaction)
            ->andThrow(new RuntimeException('BC unavailable'));
        Log::shouldReceive('warning')
            ->once()
            ->with(
                'Immediate Business Central sale sync failed; the scheduled sync will retry it.',
                [
                    'transaction_id' => 15,
                    'error' => 'BC unavailable',
                ]
            );

        $result = (new SyncSaleToBusinessCentral($service))->handle(new SellCreatedOrModified($transaction));

        $this->assertFalse($result['success']);
        $this->assertSame('BC unavailable', $result['error']);
        $this->assertStringContainsString('Sale saved in POS', $result['message']);
        $this->assertStringContainsString('BC unavailable', $result['message']);
        $this->assertStringContainsString('retry automatically', $result['message']);
    }

    public function test_disabled_bc_integration_uses_the_normal_pos_flow(): void
    {
        $transaction = new Transaction(['status' => 'final', 'type' => 'sell']);
        $transaction->id = 15;
        $transaction->business_id = 1;
        $service = $this->mock(BusinessCentralSyncService::class);
        $service->shouldReceive('enabledFor')->once()->with(1)->andReturnFalse();
        $service->shouldNotReceive('syncSale');

        $result = (new SyncSaleToBusinessCentral($service))->handle(new SellCreatedOrModified($transaction));

        $this->assertSame([
            'integration' => 'business_central',
            'enabled' => false,
            'success' => null,
        ], $result);
    }
}
