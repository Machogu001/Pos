<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        Schema::table('admin_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_last_failed_at')) {
                $table->timestamp('top_selling_low_stock_alert_last_failed_at')->nullable()->after('top_selling_low_stock_alert_last_run_at');
            }

            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_last_failure_message')) {
                $table->text('top_selling_low_stock_alert_last_failure_message')->nullable()->after('top_selling_low_stock_alert_last_failed_at');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        Schema::table('admin_settings', function (Blueprint $table) {
            $columns = [
                'top_selling_low_stock_alert_last_failed_at',
                'top_selling_low_stock_alert_last_failure_message',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('admin_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};