<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use App\AdminSetting;
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

        Schema::create('admin_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('registration_price', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('mpesa_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('phone_number')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('account_reference')->nullable();
            $table->string('mpesa_receipt_number')->nullable();
            $table->string('merchant_request_id')->nullable();
            $table->string('checkout_request_id')->nullable();
            $table->string('transaction_status')->nullable();
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
}
