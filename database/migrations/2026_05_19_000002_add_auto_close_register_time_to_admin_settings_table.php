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

        $afterColumn = Schema::hasColumn('admin_settings', 'auto_close_register')
            ? 'auto_close_register'
            : null;

        Schema::table('admin_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_settings', 'auto_close_register_time')) {
                $column = $table->string('auto_close_register_time', 5)->default('23:59');

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
            if (Schema::hasColumn('admin_settings', 'auto_close_register_time')) {
                $table->dropColumn('auto_close_register_time');
            }
        });
    }
};
