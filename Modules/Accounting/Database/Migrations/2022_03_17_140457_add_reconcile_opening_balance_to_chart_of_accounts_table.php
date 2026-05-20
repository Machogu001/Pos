<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReconcileOpeningBalanceToChartOfAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('chart_of_accounts') || Schema::hasColumn('chart_of_accounts', 'reconcile_opening_balance')) {
            return;
        }

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->integer('reconcile_opening_balance')->after('opening_balance')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('chart_of_accounts') || ! Schema::hasColumn('chart_of_accounts', 'reconcile_opening_balance')) {
            return;
        }

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn('reconcile_opening_balance');
        });
    }
}
