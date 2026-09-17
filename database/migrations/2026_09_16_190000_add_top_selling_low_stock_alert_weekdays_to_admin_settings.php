<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        Schema::table('admin_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_weekday_1')) {
                $table->unsignedTinyInteger('top_selling_low_stock_alert_weekday_1')
                    ->default(1)
                    ->after('top_selling_low_stock_alert_time');
            }

            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_weekday_2')) {
                $table->unsignedTinyInteger('top_selling_low_stock_alert_weekday_2')
                    ->default(4)
                    ->after('top_selling_low_stock_alert_weekday_1');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        Schema::table('admin_settings', function (Blueprint $table) {
            foreach (['top_selling_low_stock_alert_weekday_1', 'top_selling_low_stock_alert_weekday_2'] as $column) {
                if (Schema::hasColumn('admin_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};