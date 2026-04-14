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

    private function invokePrivate(object $object, string $method, array $arguments = [])
    {
        $reflection = new \ReflectionClass($object);
        $refMethod = $reflection->getMethod($method);
        $refMethod->setAccessible(true);

        return $refMethod->invokeArgs($object, $arguments);
    }
}
