<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCurrencyIdToChartOfAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('chart_of_accounts') || Schema::hasColumn('chart_of_accounts', 'currency_id')) {
            return;
        }

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->integer('currency_id')->default(133)->after('business_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('chart_of_accounts') || ! Schema::hasColumn('chart_of_accounts', 'currency_id')) {
            return;
        }

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn('currency_id');
        });
    }
}
