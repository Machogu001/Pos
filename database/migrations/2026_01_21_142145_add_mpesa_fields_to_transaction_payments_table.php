<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            $table->string('mpesa_phone')->nullable()->after('transaction_no');
            $table->string('checkout_request_id')->nullable()->after('mpesa_phone');
            $table->string('mpesa_receipt_number')->nullable()->after('checkout_request_id');
            $table->string('mpesa_status')->nullable()->after('mpesa_receipt_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            $table->dropColumn(['mpesa_phone', 'checkout_request_id', 'mpesa_receipt_number', 'mpesa_status']);
        });
    }
};
