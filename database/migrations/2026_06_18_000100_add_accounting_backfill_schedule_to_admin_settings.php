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

        $enabledAfterColumn = Schema::hasColumn('admin_settings', 'auto_close_register_time')
            ? 'auto_close_register_time'
            : null;

        $frequencyAfterColumn = Schema::hasColumn('admin_settings', 'accounting_backfill_enabled')
            ? 'accounting_backfill_enabled'
            : null;

        $timeAfterColumn = Schema::hasColumn('admin_settings', 'accounting_backfill_frequency')
            ? 'accounting_backfill_frequency'
            : null;

        Schema::table('admin_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_settings', 'accounting_backfill_enabled')) {
                $column = $table->boolean('accounting_backfill_enabled')
                    ->default(false);

                if ($enabledAfterColumn !== null) {
                    $column->after($enabledAfterColumn);
                }
            }

            if (! Schema::hasColumn('admin_settings', 'accounting_backfill_frequency')) {
                $column = $table->string('accounting_backfill_frequency', 30)
                    ->default('hourly');

                if ($frequencyAfterColumn !== null) {
                    $column->after($frequencyAfterColumn);
                }
            }

            if (! Schema::hasColumn('admin_settings', 'accounting_backfill_time')) {
                $column = $table->string('accounting_backfill_time', 5)
                    ->default('02:00');

                if ($timeAfterColumn !== null) {
                    $column->after($timeAfterColumn);
                }
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
