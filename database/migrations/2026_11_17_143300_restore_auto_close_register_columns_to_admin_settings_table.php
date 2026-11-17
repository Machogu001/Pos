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

        if (! Schema::hasColumn('admin_settings', 'auto_close_register')) {
            $afterColumn = Schema::hasColumn('admin_settings', 'etims_auto_transmit')
                ? 'etims_auto_transmit'
                : null;

            Schema::table('admin_settings', function (Blueprint $table) use ($afterColumn) {
                $column = $table->boolean('auto_close_register')->default(false);

                if ($afterColumn !== null) {
                    $column->after($afterColumn);
                }
            });
        }

        if (! Schema::hasColumn('admin_settings', 'auto_close_register_time')) {
            Schema::table('admin_settings', function (Blueprint $table) {
                $table->string('auto_close_register_time', 5)
                    ->default('23:59')
                    ->after('auto_close_register');
            });
        }
    }

    public function down(): void
    {
        // This migration repairs schema drift for columns owned by older migrations.
    }
};
