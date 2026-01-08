<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdjustmentTransactionIdToStocktakesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stocktakes', function (Blueprint $table) {
            // Add the column if it doesn't exist
            if (!Schema::hasColumn('stocktakes', 'adjustment_transaction_id')) {
                $table->unsignedInteger('adjustment_transaction_id')->nullable()->after('completed_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stocktakes', function (Blueprint $table) {
            $table->dropColumn('adjustment_transaction_id');
        });
    }
}
