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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('is_renewal')->default(false)->after('status');
            $table->unsignedBigInteger('previous_subscription_id')->nullable()->after('is_renewal');
            
            // Add foreign key for previous subscription reference
            $table->foreign('previous_subscription_id')
                  ->references('id')
                  ->on('subscriptions')
                  ->onDelete('set null');
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
            $table->dropForeign(['previous_subscription_id']);
            $table->dropColumn(['is_renewal', 'previous_subscription_id']);
        });
    }
};
