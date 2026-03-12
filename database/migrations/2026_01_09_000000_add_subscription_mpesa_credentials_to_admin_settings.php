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
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->string('subscription_mpesa_consumer_key')->nullable()->after('subscription_round_precision');
            $table->string('subscription_mpesa_consumer_secret')->nullable()->after('subscription_mpesa_consumer_key');
            $table->string('subscription_mpesa_shortcode')->nullable()->after('subscription_mpesa_consumer_secret');
            $table->string('subscription_mpesa_passkey')->nullable()->after('subscription_mpesa_shortcode');
            $table->string('subscription_mpesa_callback')->nullable()->after('subscription_mpesa_passkey');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_mpesa_consumer_key',
                'subscription_mpesa_consumer_secret',
                'subscription_mpesa_shortcode',
                'subscription_mpesa_passkey',
                'subscription_mpesa_callback'
            ]);
        });
    }
};
