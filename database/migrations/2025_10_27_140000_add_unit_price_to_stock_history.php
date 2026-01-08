<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('stock_history')) {
            return;
        }

        Schema::table('stock_history', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_history', 'unit_price')) {
                $table->decimal('unit_price', 22, 4)->nullable()->default(0)->after('quantity');
            }
            if (! Schema::hasColumn('stock_history', 'unit_price_sell')) {
                $table->decimal('unit_price_sell', 22, 4)->nullable()->default(0)->after('unit_price');
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
        if (! Schema::hasTable('stock_history')) {
            return;
        }

        Schema::table('stock_history', function (Blueprint $table) {
            if (Schema::hasColumn('stock_history', 'unit_price_sell')) {
                $table->dropColumn('unit_price_sell');
            }
            if (Schema::hasColumn('stock_history', 'unit_price')) {
                $table->dropColumn('unit_price');
            }
        });
    }
};
