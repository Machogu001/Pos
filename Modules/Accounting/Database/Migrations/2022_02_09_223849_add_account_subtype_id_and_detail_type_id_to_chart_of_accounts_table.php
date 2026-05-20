<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAccountSubtypeIdAndDetailTypeIdToChartOfAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('chart_of_accounts')) {
            return;
        }

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('chart_of_accounts', 'account_subtype_id')) {
                $table->bigInteger('account_subtype_id')->unsigned()->nullable()->after('payment_type_id');
            }

            if (! Schema::hasColumn('chart_of_accounts', 'detail_type_id')) {
                $table->bigInteger('detail_type_id')->unsigned()->nullable()->after('account_subtype_id');
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
        if (! Schema::hasTable('chart_of_accounts')) {
            return;
        }

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('chart_of_accounts', 'account_subtype_id')) {
                $table->dropColumn('account_subtype_id');
            }

            if (Schema::hasColumn('chart_of_accounts', 'detail_type_id')) {
                $table->dropColumn('detail_type_id');
            }
        });
    }
}
