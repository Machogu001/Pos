<?php

namespace Tests\Feature;

use App\MpesaPayment;
use App\Subscription;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SubscriptionPaymentRetryFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('username')->unique()->nullable();
            $table->string('password')->nullable();
            $table->boolean('has_active_subscription')->default(false);
            $table->dateTime('subscription_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('plan_name')->nullable();
            $table->string('billing_cycle')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->string('status')->nullable();
            $table->boolean('is_renewal')->default(false);
            $table->unsignedBigInteger('previous_subscription_id')->nullable();
            $table->unsignedBigInteger('pending_invoice_transaction_id')->nullable();
            $table->unsignedBigInteger('pending_mpesa_payment_id')->nullable();
            $table->string('checkout_request_id')->nullable();
            $table->string('mpesa_receipt')->nullable();
            $table->dateTime('activated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mpesa_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->string('phone_number')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('account_reference')->nullable();
            $table->string('mpesa_receipt_number')->nullable();
            $table->string('merchant_request_id')->nullable();
            $table->string('checkout_request_id')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('payer_name')->nullable();
            $table->string('result_code')->nullable();
            $table->string('result_desc')->nullable();
            $table->string('transaction_status')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    /** @test */
    public function it_reuses_a_failed_subscription_payment_record_and_account_reference()
    {
        $controller = $this->app->make(\App\Http\Controllers\SubscriptionController::class);
        $user = $this->makeUser();
        $subscription = $this->makePendingSubscription($user);

        $existingPayment = MpesaPayment::create([
            'user_id' => $user->id,
            'business_id' => $user->business_id,
            'subscription_id' => $subscription->id,
            'phone_number' => '254700000001',
            'amount' => 10,
            'account_reference' => 'SUB1-ABC123',
            'payment_type' => MpesaPayment::TYPE_SUBSCRIPTION,
            'transaction_status' => 'failed',
            'result_desc' => 'No response from user.',
        ]);

        $subscription->pending_mpesa_payment_id = $existingPayment->id;
        $subscription->save();

        $payment = $this->invokePrivate($controller, 'getOrCreatePayment', [$user, $subscription, '0700000001', 12.5]);

        $this->assertSame($existingPayment->id, $payment->id);
        $this->assertSame('SUB1-ABC123', $payment->account_reference);
        $this->assertSame('254700000001', $payment->phone_number);
        $this->assertSame('12.50', number_format((float) $payment->amount, 2, '.', ''));
    }

    /** @test */
    public function it_reuses_a_pending_subscription_payment_without_creating_another_record()
    {
        $controller = $this->app->make(\App\Http\Controllers\SubscriptionController::class);
        $user = $this->makeUser();
        $subscription = $this->makePendingSubscription($user);

        $existingPayment = MpesaPayment::create([
            'user_id' => $user->id,
            'business_id' => $user->business_id,
            'subscription_id' => $subscription->id,
            'phone_number' => '254700000002',
            'amount' => 15,
            'account_reference' => 'SUB2-XYZ789',
            'payment_type' => MpesaPayment::TYPE_SUBSCRIPTION,
            'transaction_status' => 'pending',
        ]);

        $payment = $this->invokePrivate($controller, 'getOrCreatePayment', [$user, $subscription, '254700000002', 15]);

        $this->assertSame($existingPayment->id, $payment->id);
        $this->assertSame(1, MpesaPayment::count());
    }

    /** @test */
    public function it_reuses_a_pending_new_subscription_instead_of_creating_another_one()
    {
        $controller = $this->app->make(\App\Http\Controllers\SubscriptionController::class);
        $user = $this->makeUser();

        $existingSubscription = Subscription::create([
            'user_id' => $user->id,
            'plan_name' => 'Monthly Plan',
            'billing_cycle' => 'monthly',
            'amount' => 5,
            'start_date' => Carbon::parse('2026-04-10 10:00:00'),
            'end_date' => Carbon::parse('2026-05-10 10:00:00'),
            'status' => 'pending',
            'is_renewal' => false,
        ]);

        $subscription = $this->invokePrivate($controller, 'getOrCreateSubscription', [$user, 'monthly', 5]);

        $this->assertSame($existingSubscription->id, $subscription->id);
        $this->assertSame(1, Subscription::count());
    }

    /** @test */
    public function it_reuses_a_pending_renewal_subscription_for_the_same_previous_subscription()
    {
        $controller = $this->app->make(\App\Http\Controllers\SubscriptionController::class);
        $user = $this->makeUser();

        $previousSubscription = Subscription::create([
            'user_id' => $user->id,
            'plan_name' => 'Monthly Plan',
            'billing_cycle' => 'monthly',
            'amount' => 5,
            'start_date' => Carbon::parse('2026-03-10 10:00:00'),
            'end_date' => Carbon::parse('2026-04-10 10:00:00'),
            'status' => 'expired',
            'is_renewal' => false,
        ]);

        $existingRenewal = Subscription::create([
            'user_id' => $user->id,
            'plan_name' => 'Monthly Plan (Renewal)',
            'billing_cycle' => 'monthly',
            'amount' => 5,
            'start_date' => Carbon::parse('2026-04-10 10:00:00'),
            'end_date' => Carbon::parse('2026-05-10 10:00:00'),
            'status' => 'pending',
            'is_renewal' => true,
            'previous_subscription_id' => $previousSubscription->id,
        ]);

        $subscription = $this->invokePrivate($controller, 'getOrCreateRenewalSubscription', [
            $user,
            $previousSubscription,
            'monthly',
            5,
            Carbon::parse('2026-04-10 10:00:00'),
        ]);

        $this->assertSame($existingRenewal->id, $subscription->id);
        $this->assertSame(2, Subscription::count());
    }

    /** @test */
    public function a_paid_subscription_payment_activates_from_the_payment_date()
    {
        $controller = $this->app->make(\App\Http\Controllers\SubscriptionController::class);
        $user = $this->makeUser();
        $subscription = $this->makePendingSubscription($user);
        $payment = $this->makePaidPayment($user, $subscription, '2026-04-12 14:30:00');

        $this->assertTrue($controller->activateSubscription($payment));

        $subscription->refresh();
        $user->refresh();

        $this->assertSame('active', $subscription->status);
        $this->assertSame('2026-04-12', $subscription->start_date->format('Y-m-d'));
        $this->assertSame('2026-05-12', $subscription->end_date->format('Y-m-d'));
        $this->assertNotNull($subscription->activated_at);
        $this->assertTrue($user->has_active_subscription);
    }

    /** @test */
    public function retrying_activation_does_not_extend_an_active_subscription_twice()
    {
        $controller = $this->app->make(\App\Http\Controllers\SubscriptionController::class);
        $user = $this->makeUser();
        $subscription = $this->makePendingSubscription($user);
        $payment = $this->makePaidPayment($user, $subscription, '2026-04-12 14:30:00');

        $this->assertTrue($controller->activateSubscription($payment));
        $firstEndDate = $subscription->fresh()->end_date->format('Y-m-d');

        Carbon::setTestNow('2026-06-01 09:00:00');
        $this->assertTrue($controller->activateSubscription($payment->fresh()));

        $this->assertSame($firstEndDate, $subscription->fresh()->end_date->format('Y-m-d'));
        Carbon::setTestNow();
    }

    /** @test */
    public function a_duplicate_callback_retries_a_paid_but_inactive_subscription()
    {
        $user = $this->makeUser();
        $subscription = $this->makePendingSubscription($user);
        $payment = $this->makePaidPayment($user, $subscription, '2026-04-12 14:30:00');
        $payment->update([
            'merchant_request_id' => 'merchant-123',
            'checkout_request_id' => 'checkout-123',
        ]);

        $request = \Illuminate\Http\Request::create('/api/mpesa/callback', 'POST', [
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' => 'merchant-123',
                    'CheckoutRequestID' => 'checkout-123',
                    'ResultCode' => 0,
                    'ResultDesc' => 'Success',
                ],
            ],
        ]);

        $response = $this->app->make(\App\Http\Controllers\MpesaCallbackController::class)
            ->handleCallback($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('active', $subscription->fresh()->status);
    }

    private function makeUser(): User
    {
        return User::create([
            'business_id' => 1,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'username' => 'jane-' . uniqid(),
            'password' => bcrypt('secret'),
        ]);
    }

    private function makePendingSubscription(User $user): Subscription
    {
        return Subscription::create([
            'user_id' => $user->id,
            'plan_name' => 'Monthly Plan',
            'billing_cycle' => 'monthly',
            'amount' => 10,
            'start_date' => Carbon::parse('2026-04-10 10:00:00'),
            'end_date' => Carbon::parse('2026-05-10 10:00:00'),
            'status' => 'pending',
            'is_renewal' => false,
        ]);
    }

    private function makePaidPayment(User $user, Subscription $subscription, string $paidAt): MpesaPayment
    {
        return MpesaPayment::create([
            'user_id' => $user->id,
            'business_id' => $user->business_id,
            'subscription_id' => $subscription->id,
            'phone_number' => '254700000003',
            'amount' => $subscription->amount,
            'account_reference' => 'SUB' . $subscription->id . '-PAID',
            'payment_type' => MpesaPayment::TYPE_SUBSCRIPTION,
            'transaction_status' => 'paid',
            'paid_at' => Carbon::parse($paidAt),
            'mpesa_receipt_number' => 'TESTRECEIPT1',
        ]);
    }

    private function invokePrivate(object $object, string $method, array $arguments = [])
    {
        $reflection = new \ReflectionClass($object);
        $refMethod = $reflection->getMethod($method);
        $refMethod->setAccessible(true);

        return $refMethod->invokeArgs($object, $arguments);
    }
}
