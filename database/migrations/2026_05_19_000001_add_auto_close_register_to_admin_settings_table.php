<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_settings', 'auto_close_register')) {
                $table->boolean('auto_close_register')->default(false)->after('etims_auto_transmit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            if (Schema::hasColumn('admin_settings', 'auto_close_register')) {
                $table->dropColumn('auto_close_register');
            }
        });
    }
};
