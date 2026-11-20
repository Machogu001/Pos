<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        // subscriptions.id is INT UNSIGNED when created by the Superadmin module,
        // BIGINT UNSIGNED otherwise — the self-referencing column must match it.
        $col = DB::selectOne(
            "SELECT DATA_TYPE AS t FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subscriptions' AND COLUMN_NAME = 'id'"
        );
        $idIsBigInt = $col && strtolower($col->t) === 'bigint';

        Schema::table('subscriptions', function (Blueprint $table) use ($idIsBigInt) {
            $table->boolean('is_renewal')->default(false)->after('status');
            if ($idIsBigInt) {
                $table->unsignedBigInteger('previous_subscription_id')->nullable()->after('is_renewal');
            } else {
                $table->unsignedInteger('previous_subscription_id')->nullable()->after('is_renewal');
            }
            
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
