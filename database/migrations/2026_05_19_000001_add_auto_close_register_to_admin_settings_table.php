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

        $afterColumn = Schema::hasColumn('admin_settings', 'etims_auto_transmit')
            ? 'etims_auto_transmit'
            : null;

        Schema::table('admin_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_settings', 'auto_close_register')) {
                $column = $table->boolean('auto_close_register')->default(false);

                if ($afterColumn !== null) {
                    $column->after($afterColumn);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        Schema::table('admin_settings', function (Blueprint $table) {
            if (Schema::hasColumn('admin_settings', 'auto_close_register')) {
                $table->dropColumn('auto_close_register');
            }
        });
    }
};
