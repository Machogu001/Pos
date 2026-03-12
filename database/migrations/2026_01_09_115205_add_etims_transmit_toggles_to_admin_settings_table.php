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
            $table->boolean('etims_transmit_subscriptions')->default(false)->after('etims_auto_transmit');
            $table->boolean('etims_transmit_registrations')->default(false)->after('etims_transmit_subscriptions');
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
            $table->dropColumn(['etims_transmit_subscriptions', 'etims_transmit_registrations']);
        });
    }
};
