<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('admin_settings')) return;

        Schema::table('admin_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_settings', 'subscription_round_precision')) {
                // Number of decimal places to round subscription final_total to (0 = whole number)
                $table->unsignedTinyInteger('subscription_round_precision')->default(0)->after('subscription_vat_percent');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('admin_settings')) return;

        Schema::table('admin_settings', function (Blueprint $table) {
            if (Schema::hasColumn('admin_settings', 'subscription_round_precision')) {
                $table->dropColumn('subscription_round_precision');
            }
        });
    }
};
