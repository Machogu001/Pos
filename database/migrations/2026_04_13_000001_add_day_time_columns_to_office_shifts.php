<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('office_shifts')) {
            return;
        }

        Schema::table('office_shifts', function (Blueprint $table) {
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            foreach ($days as $day) {
                if (!Schema::hasColumn('office_shifts', "{$day}_in")) {
                    $table->string("{$day}_in", 10)->nullable();
                }
                if (!Schema::hasColumn('office_shifts', "{$day}_out")) {
                    $table->string("{$day}_out", 10)->nullable();
                }
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('office_shifts')) {
            return;
        }

        Schema::table('office_shifts', function (Blueprint $table) {
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            foreach ($days as $day) {
                if (Schema::hasColumn('office_shifts', "{$day}_in")) {
                    $table->dropColumn("{$day}_in");
                }
                if (Schema::hasColumn('office_shifts', "{$day}_out")) {
                    $table->dropColumn("{$day}_out");
                }
            }
        });
    }
};
