<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDepartmentHeadToDepartments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('departments') && !Schema::hasColumn('departments', 'department_head')) {
            Schema::table('departments', function (Blueprint $table) {
                // nullable integer to reference employee id (no FK by default to avoid migrations ordering issues)
                $table->unsignedBigInteger('department_head')->nullable()->after('company_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'department_head')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropColumn('department_head');
            });
        }
    }
}
