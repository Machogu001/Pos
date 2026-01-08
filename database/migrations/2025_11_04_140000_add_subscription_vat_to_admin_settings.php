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
            if (! Schema::hasColumn('admin_settings', 'subscription_vat_percent')) {
                // store percentage with 2 decimals, default 0.00
                $table->decimal('subscription_vat_percent', 8, 2)->default(0)->after('subscription_invoice_next');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('admin_settings')) return;

        Schema::table('admin_settings', function (Blueprint $table) {
            if (Schema::hasColumn('admin_settings', 'subscription_vat_percent')) {
                $table->dropColumn('subscription_vat_percent');
            }
        });
    }
};
