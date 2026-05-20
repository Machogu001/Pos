<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaymentTypeIdToChartOfAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('chart_of_accounts') || Schema::hasColumn('chart_of_accounts', 'payment_type_id')) {
            return;
        }

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->bigInteger('payment_type_id')->unsigned()->default(1)->after('currency_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('chart_of_accounts') || ! Schema::hasColumn('chart_of_accounts', 'payment_type_id')) {
            return;
        }

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn('payment_type_id');
        });
    }
}
