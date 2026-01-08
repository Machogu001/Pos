<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mpesa_payments', function (Blueprint $table) {
            $table->id();            
            $table->unsignedInteger('user_id')->nullable(); // match users.id
            $table->unsignedBigInteger('subscription_id')->nullable(); // match subscriptions.id
            $table->string('payer_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone_number');
            $table->string('account_reference')->unique(); // unique reference to match STK
            $table->decimal('amount', 10, 2);
            $table->string('mpesa_receipt_number')->nullable();
            $table->string('merchant_request_id')->nullable();
            $table->string('checkout_request_id')->nullable();
            $table->string('result_code')->nullable(); // Safaricom STK result code
            $table->string('result_desc')->nullable(); // Safaricom STK result description
            $table->string('transaction_status')->default('PENDING'); // e.g. SUCCESS, FAILED
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Only add user foreign key now
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('set null');

            // DON'T add subscription foreign key here yet
            // We'll add it in a separate migration
        });
    }

    public function down()
    {
        Schema::dropIfExists('mpesa_payments');
    }
};