<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDefaultAnnualLeaveToAdminSettings extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        Schema::table('admin_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_settings', 'default_annual_leave')) {
                $table->integer('default_annual_leave')->nullable()->default(21)->after('recent_limit');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        Schema::table('admin_settings', function (Blueprint $table) {
            if (Schema::hasColumn('admin_settings', 'default_annual_leave')) {
                $table->dropColumn('default_annual_leave');
            }
        });
    }
}
