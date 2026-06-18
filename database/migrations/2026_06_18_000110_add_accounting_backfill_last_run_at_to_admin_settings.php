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
            if (! Schema::hasColumn('admin_settings', 'accounting_backfill_last_run_at')) {
                $table->dateTime('accounting_backfill_last_run_at')
                    ->nullable()
                    ->after('accounting_backfill_time');
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
            if (Schema::hasColumn('admin_settings', 'accounting_backfill_last_run_at')) {
                $table->dropColumn('accounting_backfill_last_run_at');
            }
        });
    }
};
