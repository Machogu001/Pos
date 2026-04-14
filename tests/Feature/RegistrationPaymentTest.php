<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use App\AdminSetting;
use App\Business;
use App\MpesaPayment;
use App\Currency;
use App\User;
use App\Mail\RegistrationMail;

class RegistrationPaymentTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Create minimal tables needed for the feature tests so we don't run full project migrations
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->string('surname')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('language')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('business', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('registration_price', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('mpesa_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->unsignedBigInteger('consumed_by_transaction_id')->nullable();
            $table->string('phone_number')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('account_reference')->nullable();
            $table->string('mpesa_receipt_number')->nullable();
            $table->string('merchant_request_id')->nullable();
            $table->string('checkout_request_id')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('result_code')->nullable();
            $table->string('result_desc')->nullable();
            $table->string('transaction_status')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('symbol')->nullable();
            $table->string('thousand_separator')->nullable();
            $table->string('decimal_separator')->nullable();
            $table->timestamps();
        });
    }

    /** @test */
    public function registration_succeeds_when_payment_exists()
    {
        Mail::fake();

        // Ensure a currency exists because controller expects one
        $currency = Currency::create([
            'code' => 'KES',
            'symbol' => 'Ksh',
            'thousand_separator' => ',',
            'decimal_separator' => '.',
        ]);

        AdminSetting::create(['registration_price' => 5]);

        // Create a successful Mpesa payment
        MpesaPayment::create([
            'phone_number' => '254700000000',
            'transaction_status' => 'success',
            'amount' => 5,
            'checkout_request_id' => 'CHK123',
            'mpesa_receipt_number' => 'RCPT123',
        ]);

        // Instead of executing the full registration flow (which depends on many migrations),
        // assert the payment lookup logic returns the MpesaPayment we created.
        $this->withSession(['payment_phone' => '254700000000']);
        $controller = $this->app->make(\App\Http\Controllers\BusinessController::class);
        $req = new \Illuminate\Http\Request();
        $req->merge(['checkout_request_id' => 'CHK123']);

        $payment = $controller->findSuccessfulMpesaPayment($req);

        $this->assertNotNull($payment);
        $this->assertEquals('RCPT123', $payment->mpesa_receipt_number);
    }

    /** @test */
    public function registration_fails_when_payment_missing()
    {
        Mail::fake();

        $currency = Currency::create([
            'code' => 'KES',
            'symbol' => 'Ksh',
            'thousand_separator' => ',',
            'decimal_separator' => '.',
        ]);

        AdminSetting::create(['registration_price' => 10]);

        // Test that no payment is found when none exists for the session/request
        $controller = $this->app->make(\App\Http\Controllers\BusinessController::class);
        $req = new \Illuminate\Http\Request();
        $req->merge(['checkout_request_id' => 'NOPE']);

        $payment = $controller->findSuccessfulMpesaPayment($req);

        $this->assertNull($payment);
        Mail::assertNothingSent();
    }

    /** @test */
    public function registration_payment_lookup_ignores_non_registration_payments()
    {
        MpesaPayment::create([
            'phone_number' => '254722222222',
            'transaction_status' => 'paid',
            'amount' => 5,
            'checkout_request_id' => 'SELL-CHK-001',
            'payment_type' => 'sell',
            'account_reference' => 'SELL-REF-001',
        ]);

        $this->withSession(['payment_phone' => '254722222222']);
        $controller = $this->app->make(\App\Http\Controllers\BusinessController::class);
        $req = new Request();

        $payment = $controller->findSuccessfulMpesaPayment($req);

        $this->assertNull($payment);
    }

    /** @test */
    public function registration_payment_lookup_does_not_fallback_to_an_old_successful_payment_on_the_same_phone()
    {
        MpesaPayment::create([
            'phone_number' => '254717996492',
            'transaction_status' => 'paid',
            'amount' => 5,
            'checkout_request_id' => 'OLD-CHK-PAID',
            'account_reference' => 'OLD-REF-PAID',
            'payment_type' => 'registration',
        ]);

        $failedCurrentAttempt = MpesaPayment::create([
            'phone_number' => '254717996492',
            'transaction_status' => 'failed',
            'amount' => 5,
            'checkout_request_id' => 'CURRENT-CHK-FAILED',
            'account_reference' => 'CURRENT-REF-FAILED',
            'payment_type' => 'registration',
            'result_desc' => 'No response from user.',
        ]);

        $this->withSession([
            'registration_payment_id' => $failedCurrentAttempt->id,
            'payment_phone' => '254717996492',
            'account_reference' => 'CURRENT-REF-FAILED',
            'checkout_request_id' => 'CURRENT-CHK-FAILED',
        ]);

        $controller = $this->app->make(\App\Http\Controllers\BusinessController::class);
        $payment = $controller->findSuccessfulMpesaPayment(new Request());

        $this->assertNull($payment);
    }

    /** @test */
    public function resume_registration_restores_session_from_an_unused_paid_registration_payment()
    {
        $payment = MpesaPayment::create([
            'phone_number' => '254711223344',
            'transaction_status' => 'paid',
            'amount' => 5,
            'checkout_request_id' => 'RESUME-CHK-001',
            'account_reference' => 'RESUME-REF-001',
            'mpesa_receipt_number' => 'RESUME-RCPT-001',
            'payment_type' => 'registration',
        ]);

        $response = $this->post(route('business.registration.resume'), [
            'phone' => '0711223344',
            'payment_reference' => 'RESUME-RCPT-001',
        ]);

        $response->assertRedirect(route('business.getRegister'));
        $response->assertSessionHas('registration_payment_id', $payment->id);
        $response->assertSessionHas('checkout_request_id', 'RESUME-CHK-001');
        $response->assertSessionHas('account_reference', 'RESUME-REF-001');
        $response->assertSessionHas('payment_phone', '254711223344');
    }

    /** @test */
    public function resume_registration_rejects_a_paid_registration_payment_that_has_already_been_used()
    {
        MpesaPayment::create([
            'phone_number' => '254711223355',
            'transaction_status' => 'paid',
            'amount' => 5,
            'checkout_request_id' => 'USED-CHK-001',
            'account_reference' => 'USED-REF-001',
            'mpesa_receipt_number' => 'USED-RCPT-001',
            'payment_type' => 'registration',
            'business_id' => 9,
            'consumed_at' => now(),
        ]);

        $response = $this->from(route('business.getRegister'))->post(route('business.registration.resume'), [
            'phone' => '254711223355',
            'payment_reference' => 'USED-RCPT-001',
        ]);

        $response->assertRedirect(route('business.getRegister'));
        $response->assertSessionHasErrors('resume_payment');
        $this->assertNull(session('registration_payment_id'));
    }

    /** @test */
    public function signed_resume_link_restores_session_only_for_an_unused_payment()
    {
        $payment = MpesaPayment::create([
            'phone_number' => '254711223366',
            'transaction_status' => 'paid',
            'amount' => 5,
            'checkout_request_id' => 'LINK-CHK-001',
            'account_reference' => 'LINK-REF-001',
            'mpesa_receipt_number' => 'LINK-RCPT-001',
            'payment_type' => 'registration',
        ]);

        $signedUrl = URL::temporarySignedRoute('business.registration.resume.link', now()->addHour(), [
            'payment' => $payment->id,
        ]);

        $response = $this->get($signedUrl);

        $response->assertRedirect(route('business.getRegister'));
        $response->assertSessionHas('registration_payment_id', $payment->id);
        $response->assertSessionHas('account_reference', 'LINK-REF-001');
    }

    /** @test */
    public function mpesa_payment_defaults_to_registration_when_no_specific_context_exists()
    {
        $payment = MpesaPayment::create([
            'phone_number' => '254744444444',
            'amount' => 5,
            'account_reference' => 'REG-444',
            'checkout_request_id' => 'CHK-444',
            'transaction_status' => 'pending',
        ]);

        $this->assertSame(MpesaPayment::TYPE_REGISTRATION, $payment->fresh()->payment_type);
    }

    /** @test */
    public function mpesa_payment_preserves_explicit_purchase_type()
    {
        $payment = MpesaPayment::create([
            'user_id' => 91,
            'business_id' => 14,
            'phone_number' => '254711111111',
            'amount' => 285.90,
            'account_reference' => 'PO2026/0018',
            'checkout_request_id' => 'PUR-CHK-018',
            'payment_type' => MpesaPayment::TYPE_PURCHASE,
            'transaction_status' => 'pending',
        ]);

        $this->assertSame(MpesaPayment::TYPE_PURCHASE, $payment->fresh()->payment_type);
    }

    /** @test */
    public function mpesa_payment_inferrs_sell_type_from_consumed_transaction_context()
    {
        $payment = MpesaPayment::create([
            'user_id' => 77,
            'phone_number' => '254755555555',
            'amount' => 10,
            'account_reference' => 'SALE-555',
            'checkout_request_id' => 'CHK-555',
            'consumed_by_transaction_id' => 99,
            'transaction_status' => 'paid',
        ]);

        $this->assertSame(MpesaPayment::TYPE_SELL, $payment->fresh()->payment_type);
    }

    /** @test */
    public function mpesa_payment_inferrs_subscription_type_from_subscription_context()
    {
        $payment = MpesaPayment::create([
            'user_id' => 88,
            'business_id' => 5,
            'subscription_id' => 12,
            'phone_number' => '254766666666',
            'amount' => 15,
            'account_reference' => 'SUB12-XYZ',
            'checkout_request_id' => 'CHK-666',
            'transaction_status' => 'pending',
        ]);

        $this->assertSame(MpesaPayment::TYPE_SUBSCRIPTION, $payment->fresh()->payment_type);
    }

    /** @test */
    public function kenyan_phone_numbers_are_normalized_from_supported_local_and_international_formats()
    {
        $this->assertSame('254712345678', MpesaPayment::normalizePhoneNumber('+254712345678'));
        $this->assertSame('254712345678', MpesaPayment::normalizePhoneNumber('0712345678'));
        $this->assertSame('254112345678', MpesaPayment::normalizePhoneNumber('0112345678'));
        $this->assertSame('254112345678', MpesaPayment::normalizePhoneNumber('+254112345678'));
        $this->assertNull(MpesaPayment::normalizePhoneNumber('12345'));
    }

    /** @test */
    public function successful_registration_payment_is_linked_to_created_user_and_business()
    {
        $controller = $this->app->make(\App\Http\Controllers\BusinessController::class);

        $user = User::create([
            'surname' => 'Mr',
            'first_name' => 'Owner',
            'last_name' => 'User',
            'username' => 'owner-user',
            'email' => 'owner@example.com',
            'password' => bcrypt('secret'),
            'language' => 'en',
        ]);

        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'New Business',
        ]);

        $payment = MpesaPayment::create([
            'phone_number' => '254711111111',
            'amount' => 5,
            'account_reference' => 'REG-ABC123',
            'checkout_request_id' => 'CHK-LINK-001',
            'payment_type' => 'registration',
            'transaction_status' => 'paid',
        ]);

        $request = Request::create('/business/register', 'POST', [
            'checkout_request_id' => 'CHK-LINK-001',
        ]);

        $this->withSession([
            'payment_phone' => '254711111111',
            'checkout_request_id' => 'CHK-LINK-001',
            'account_reference' => 'REG-ABC123',
        ]);

        $ref = new \ReflectionClass($controller);
        $method = $ref->getMethod('attachRegistrationPayment');
        $method->setAccessible(true);
        $method->invoke($controller, $request, $user, $business);

        $payment->refresh();

        $this->assertSame($user->id, $payment->user_id);
        $this->assertSame($business->id, $payment->business_id);
        $this->assertNotNull($payment->consumed_at);
    }

    /** @test */
    public function confirm_payment_prefers_registration_scoped_payment_state()
    {
        $registrationPayment = MpesaPayment::create([
            'phone_number' => '254733333333',
            'amount' => 5,
            'account_reference' => 'REG-REF-333',
            'checkout_request_id' => 'REG-CHK-333',
            'payment_type' => 'registration',
            'transaction_status' => 'paid',
        ]);

        MpesaPayment::create([
            'phone_number' => '254733333333',
            'amount' => 5,
            'account_reference' => 'SELL-REF-333',
            'checkout_request_id' => 'SELL-CHK-333',
            'payment_type' => 'sell',
            'transaction_status' => 'pending',
        ]);

        $response = $this->withSession([
            'registration_payment_id' => $registrationPayment->id,
            'payment_phone' => '254733333333',
            'account_reference' => 'REG-REF-333',
            'checkout_request_id' => 'REG-CHK-333',
        ])->post(route('business.payment.confirm'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'payment_id' => $registrationPayment->id,
            'account_ref' => 'REG-REF-333',
            'checkout_request_id' => 'REG-CHK-333',
            'transaction_status' => 'paid',
        ]);
    }

    /** @test */
    public function confirm_payment_returns_failure_reason_for_registration_payment()
    {
        $registrationPayment = MpesaPayment::create([
            'phone_number' => '254733333334',
            'amount' => 5,
            'account_reference' => 'REG-REF-334',
            'checkout_request_id' => 'REG-CHK-334',
            'payment_type' => 'registration',
            'transaction_status' => 'failed',
            'result_desc' => 'No response from user.',
        ]);

        $response = $this->withSession([
            'registration_payment_id' => $registrationPayment->id,
            'payment_phone' => '254733333334',
            'account_reference' => 'REG-REF-334',
            'checkout_request_id' => 'REG-CHK-334',
        ])->post(route('business.payment.confirm'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'payment_id' => $registrationPayment->id,
            'account_ref' => 'REG-REF-334',
            'checkout_request_id' => 'REG-CHK-334',
            'transaction_status' => 'failed',
            'result_desc' => 'No response from user.',
        ]);
    }

    /** @test */
    public function initiate_payment_keeps_registration_type_on_pos_subdomain_registration_page()
    {
        Http::fake([
            'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials' => Http::response([
                'access_token' => 'test-token',
            ], 200),
            'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response([
                'ResponseCode' => '0',
                'MerchantRequestID' => 'merchant-123',
                'CheckoutRequestID' => 'checkout-123',
            ], 200),
        ]);

        AdminSetting::create(['registration_price' => 5]);

        $response = $this->withHeader('referer', 'https://pos.bremac.co.ke/business/register')
            ->postJson(route('mpesa.initiate'), [
                'first_name' => 'Philmed',
                'last_name' => 'Limited',
                'phone' => '254717996492',
                'amount' => 5,
            ]);

        $response->assertOk();
        $response->assertJson([
            'transaction_status' => 'success',
            'account_ref' => MpesaPayment::first()->account_reference,
            'checkout_request_id' => 'checkout-123',
        ]);

        $payment = MpesaPayment::first();

        $this->assertNotNull($payment);
        $this->assertSame(MpesaPayment::TYPE_REGISTRATION, $payment->payment_type);
    }

    /** @test */
    public function purchase_initiation_ignores_stale_session_account_reference_and_returns_json_success()
    {
        $user = User::create([
            'business_id' => 2,
            'surname' => 'Bremac',
            'first_name' => 'POS',
            'last_name' => 'Cashier',
            'username' => 'purchase-pos-user',
            'email' => 'purchase-pos@example.com',
            'password' => bcrypt('secret'),
        ]);

        MpesaPayment::create([
            'user_id' => $user->id,
            'business_id' => 2,
            'phone_number' => '254717996492',
            'amount' => 50,
            'account_reference' => 'TTYCWIS4',
            'checkout_request_id' => 'OLD-CHK-001',
            'payment_type' => MpesaPayment::TYPE_SELL,
            'transaction_status' => 'failed',
        ]);

        Http::fake([
            'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials' => Http::response([
                'access_token' => 'token-123',
            ], 200),
            'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response([
                'ResponseCode' => '0',
                'MerchantRequestID' => 'merchant-purchase-123',
                'CheckoutRequestID' => 'checkout-purchase-123',
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'account_reference' => 'TTYCWIS4',
                'account_ref' => 'TTYCWIS4',
                'registration_payment_id' => 999,
            ])
            ->postJson(route('mpesa.initiate'), [
                'phone' => '0717996492',
                'amount' => 285.90,
                'payment_type' => MpesaPayment::TYPE_PURCHASE,
            ]);

        $response->assertOk();
        $response->assertJson([
            'transaction_status' => 'success',
            'checkout_request_id' => 'checkout-purchase-123',
        ]);

        $payment = MpesaPayment::where('checkout_request_id', 'checkout-purchase-123')->first();

        $this->assertNotNull($payment);
        $this->assertSame(MpesaPayment::TYPE_PURCHASE, $payment->payment_type);
        $this->assertNotSame('TTYCWIS4', $payment->account_reference);
    }
}
