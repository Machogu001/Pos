<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'is_stock_adjustment')) {
                $table->boolean('is_stock_adjustment')
                      ->default(0)
                      ->after('transaction_date'); // Or place it elsewhere if needed
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
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'is_stock_adjustment')) {
                $table->dropColumn('is_stock_adjustment');
            }
        });
    }
};
