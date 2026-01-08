<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPendingLinksToSubscriptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('pending_invoice_transaction_id')->nullable()->after('reminder_sent_at')->index();
            $table->unsignedBigInteger('pending_mpesa_payment_id')->nullable()->after('pending_invoice_transaction_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['pending_invoice_transaction_id', 'pending_mpesa_payment_id']);
        });
    }
}
