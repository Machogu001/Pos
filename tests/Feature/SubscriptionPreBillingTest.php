<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\User;
use App\Subscription;
use App\MpesaPayment;
use Illuminate\Support\Facades\Artisan;

class SubscriptionPreBillingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_invoice_and_pending_mpesa_for_subscriptions_14_days_before_end()
    {
        // Create a user and a subscription that ends in 14 days
        $user = User::factory()->create();

        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_name' => 'Test Plan',
            'billing_cycle' => 'monthly',
            'amount' => 1000,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addDays(14),
            'status' => 'active',
        ]);

        // Run the reminders command
        Artisan::call('subscriptions:send_reminders');

        $sub->refresh();

        $this->assertNotNull($sub->invoice_sent_at, 'invoice_sent_at should be set after running reminders');
        $this->assertNotNull($sub->pending_invoice_transaction_id, 'pending_invoice_transaction_id should be set');
        $this->assertNotNull($sub->pending_mpesa_payment_id, 'pending_mpesa_payment_id should be set');

        // Verify MpesaPayment exists
        $mp = MpesaPayment::find($sub->pending_mpesa_payment_id);
        $this->assertNotNull($mp);
    }
}
