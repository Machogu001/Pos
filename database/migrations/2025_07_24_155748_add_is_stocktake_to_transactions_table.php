<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsStocktakeToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('transactions', 'is_stocktake')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->boolean('is_stocktake')
                    ->default(0)
                    ->after('status')
                    ->index();
            });
        }
    }

    /**
     * Reverse the migrations (rollback).
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('transactions', 'is_stocktake')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('is_stocktake');
            });
        }
    }
}
