<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConsumedFieldsToMpesaPayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mpesa_payments', function (Blueprint $table) {
            // Track which transaction consumed this mpesa payment to prevent reuse
            $table->unsignedBigInteger('consumed_by_transaction_id')->nullable()->after('subscription_id');
            $table->timestamp('consumed_at')->nullable()->after('consumed_by_transaction_id');
            $table->index('consumed_by_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mpesa_payments', function (Blueprint $table) {
            $table->dropIndex(['consumed_by_transaction_id']);
            $table->dropColumn(['consumed_by_transaction_id', 'consumed_at']);
        });
    }
}
