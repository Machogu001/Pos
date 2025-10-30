<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'business_id')) {
                $table->unsignedBigInteger('business_id')->nullable()->after('guard_name');
            }

            if (!Schema::hasColumn('roles', 'is_default')) {
                $table->boolean('is_default')->default(0)->after('business_id');
            }

            if (!Schema::hasColumn('roles', 'is_service_staff')) {
                $table->boolean('is_service_staff')->default(0)->after('is_default');
            }
        });
    }

    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'is_service_staff')) {
                $table->dropColumn('is_service_staff');
            }
            if (Schema::hasColumn('roles', 'is_default')) {
                $table->dropColumn('is_default');
            }
            if (Schema::hasColumn('roles', 'business_id')) {
                $table->dropColumn('business_id');
            }
        });
    }
};
