<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_settings', 'auto_close_register_time')) {
                $table->string('auto_close_register_time', 5)->default('23:59')
                      ->after('auto_close_register');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            if (Schema::hasColumn('admin_settings', 'auto_close_register_time')) {
                $table->dropColumn('auto_close_register_time');
            }
        });
    }
};
