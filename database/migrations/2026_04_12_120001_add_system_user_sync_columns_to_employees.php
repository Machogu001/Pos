<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'system_user_id')) {
                $table->unsignedBigInteger('system_user_id')->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('employees', 'is_system_user')) {
                $table->boolean('is_system_user')->default(false)->after('system_user_id')->index();
            }
            if (! Schema::hasColumn('employees', 'sync_disabled')) {
                $table->boolean('sync_disabled')->default(false)->after('is_system_user')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'sync_disabled')) {
                $table->dropColumn('sync_disabled');
            }
            if (Schema::hasColumn('employees', 'is_system_user')) {
                $table->dropColumn('is_system_user');
            }
            if (Schema::hasColumn('employees', 'system_user_id')) {
                $table->dropColumn('system_user_id');
            }
        });
    }
};
