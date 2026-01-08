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
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->after('lot_number');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->boolean('is_stock_adjustment')->default(false)->after('transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropColumn('expiry_date');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('is_stock_adjustment');
        });
    }
};
