<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'bonus_trigger_quantity')) {
                    $table->decimal('bonus_trigger_quantity', 22, 4)->nullable()->after('not_for_selling');
                }
                if (! Schema::hasColumn('products', 'bonus_free_quantity')) {
                    $table->decimal('bonus_free_quantity', 22, 4)->nullable()->after('bonus_trigger_quantity');
                }
            });
        }

        if (Schema::hasTable('transaction_sell_lines')) {
            Schema::table('transaction_sell_lines', function (Blueprint $table) {
                if (! Schema::hasColumn('transaction_sell_lines', 'bonus_quantity')) {
                    $table->decimal('bonus_quantity', 22, 4)->default(0)->after('quantity');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transaction_sell_lines')) {
            Schema::table('transaction_sell_lines', function (Blueprint $table) {
                if (Schema::hasColumn('transaction_sell_lines', 'bonus_quantity')) {
                    $table->dropColumn('bonus_quantity');
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $drops = [];
                if (Schema::hasColumn('products', 'bonus_trigger_quantity')) {
                    $drops[] = 'bonus_trigger_quantity';
                }
                if (Schema::hasColumn('products', 'bonus_free_quantity')) {
                    $drops[] = 'bonus_free_quantity';
                }
                if (! empty($drops)) {
                    $table->dropColumn($drops);
                }
            });
        }
    }
};