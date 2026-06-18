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
            if (! Schema::hasColumn('admin_settings', 'accounting_backfill_enabled')) {
                $table->boolean('accounting_backfill_enabled')
                    ->default(false)
                    ->after('auto_close_register_time');
            }

            if (! Schema::hasColumn('admin_settings', 'accounting_backfill_frequency')) {
                $table->string('accounting_backfill_frequency', 30)
                    ->default('hourly')
                    ->after('accounting_backfill_enabled');
            }

            if (! Schema::hasColumn('admin_settings', 'accounting_backfill_time')) {
                $table->string('accounting_backfill_time', 5)
                    ->default('02:00')
                    ->after('accounting_backfill_frequency');
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
                'accounting_backfill_time',
                'accounting_backfill_frequency',
                'accounting_backfill_enabled',
            ] as $column) {
                if (Schema::hasColumn('admin_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
