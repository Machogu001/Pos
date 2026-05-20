<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOpeningBalanceToChartOfAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('chart_of_accounts') || Schema::hasColumn('chart_of_accounts', 'opening_balance')) {
            return;
        }

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->decimal('opening_balance', 11, 2)->default(0)->after('account_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('chart_of_accounts') || ! Schema::hasColumn('chart_of_accounts', 'opening_balance')) {
            return;
        }

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn('opening_balance');
        });
    }
}
