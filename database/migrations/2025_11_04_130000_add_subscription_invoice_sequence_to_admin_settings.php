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
            if (! Schema::hasColumn('admin_settings', 'subscription_invoice_prefix')) {
                $table->string('subscription_invoice_prefix')->nullable()->after('statement_footer');
            }
            if (! Schema::hasColumn('admin_settings', 'subscription_invoice_next')) {
                $table->unsignedBigInteger('subscription_invoice_next')->default(1)->after('subscription_invoice_prefix');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('admin_settings')) return;

        Schema::table('admin_settings', function (Blueprint $table) {
            if (Schema::hasColumn('admin_settings', 'subscription_invoice_next')) {
                $table->dropColumn('subscription_invoice_next');
            }
            if (Schema::hasColumn('admin_settings', 'subscription_invoice_prefix')) {
                $table->dropColumn('subscription_invoice_prefix');
            }
        });
    }
};
