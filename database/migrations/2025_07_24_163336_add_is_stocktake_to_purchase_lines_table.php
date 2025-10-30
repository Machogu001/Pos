<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsStocktakeToPurchaseLinesTable extends Migration
{
    public function up()
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->boolean('is_stocktake')->default(0)->after('transaction_id')->index();
        });
    }

    public function down()
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropColumn('is_stocktake');
        });
    }
}
