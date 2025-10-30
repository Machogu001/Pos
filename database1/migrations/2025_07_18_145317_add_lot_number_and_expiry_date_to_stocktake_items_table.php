<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLotNumberAndExpiryDateToStocktakeItemsTable extends Migration
{
    public function up()
    {
        Schema::table('stocktake_items', function (Blueprint $table) {
            $table->string('lot_number')->nullable()->after('notes');
            $table->date('expiry_date')->nullable()->after('lot_number');
        });
    }

    public function down()
    {
        Schema::table('stocktake_items', function (Blueprint $table) {
            $table->dropColumn(['lot_number', 'expiry_date']);
        });
    }
}