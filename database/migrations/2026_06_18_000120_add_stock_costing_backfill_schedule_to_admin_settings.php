<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        Schema::table('admin_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_settings', 'stock_costing_backfill_enabled')) {
                $table->boolean('stock_costing_backfill_enabled')
                    ->default(false)
                    ->after('accounting_backfill_last_run_at');
            }

            if (! Schema::hasColumn('admin_settings', 'stock_costing_backfill_frequency')) {
                $table->string('stock_costing_backfill_frequency', 30)
                    ->default('daily')
                    ->after('stock_costing_backfill_enabled');
            }

            if (! Schema::hasColumn('admin_settings', 'stock_costing_backfill_time')) {
                $table->string('stock_costing_backfill_time', 5)
                    ->default('01:30')
                    ->after('stock_costing_backfill_frequency');
            }

            if (! Schema::hasColumn('admin_settings', 'stock_costing_backfill_business_id')) {
                $table->unsignedInteger('stock_costing_backfill_business_id')
                    ->nullable()
                    ->after('stock_costing_backfill_time');
            }

            if (! Schema::hasColumn('admin_settings', 'stock_costing_backfill_location_id')) {
                $table->unsignedInteger('stock_costing_backfill_location_id')
                    ->nullable()
                    ->after('stock_costing_backfill_business_id');
            }

            if (! Schema::hasColumn('admin_settings', 'stock_costing_backfill_last_run_at')) {
                $table->dateTime('stock_costing_backfill_last_run_at')
                    ->nullable()
                    ->after('stock_costing_backfill_location_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        Schema::table('admin_settings', function (Blueprint $table) {
            foreach ([
                'stock_costing_backfill_last_run_at',
                'stock_costing_backfill_location_id',
                'stock_costing_backfill_business_id',
                'stock_costing_backfill_time',
                'stock_costing_backfill_frequency',
                'stock_costing_backfill_enabled',
            ] as $column) {
                if (Schema::hasColumn('admin_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
