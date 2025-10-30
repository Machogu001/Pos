<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mpesa_payments', function (Blueprint $table) {
            $table->foreign('subscription_id')
                ->references('id')->on('subscriptions')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('mpesa_payments', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
        });
    }
};