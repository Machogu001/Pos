<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOfficeShiftsAddColumns extends Migration
{
    public function up()
    {
        if (Schema::hasTable('office_shifts')) {
            Schema::table('office_shifts', function (Blueprint $table) {
                if (! Schema::hasColumn('office_shifts', 'start_time')) {
                    $table->time('start_time')->nullable()->after('name');
                }
                if (! Schema::hasColumn('office_shifts', 'end_time')) {
                    $table->time('end_time')->nullable()->after('start_time');
                }
                if (! Schema::hasColumn('office_shifts', 'break_minutes')) {
                    $table->integer('break_minutes')->nullable()->after('end_time');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('office_shifts')) {
            Schema::table('office_shifts', function (Blueprint $table) {
                if (Schema::hasColumn('office_shifts', 'break_minutes')) {
                    $table->dropColumn('break_minutes');
                }
                if (Schema::hasColumn('office_shifts', 'end_time')) {
                    $table->dropColumn('end_time');
                }
                if (Schema::hasColumn('office_shifts', 'start_time')) {
                    $table->dropColumn('start_time');
                }
            });
        }
    }
}
