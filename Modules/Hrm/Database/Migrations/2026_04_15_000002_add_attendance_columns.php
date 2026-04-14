<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttendanceColumns extends Migration
{
    public function up()
    {
        if (Schema::hasTable('attendances') && !Schema::hasColumn('attendances', 'company_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                // Add missing columns that the controller expects
                $table->unsignedBigInteger('company_id')->nullable()->after('employee_id');
                $table->time('total_work')->nullable()->after('clock_out');
                $table->time('late_time')->nullable()->after('total_work');
                $table->time('depart_early')->nullable()->after('late_time');
                $table->time('overtime')->nullable()->after('depart_early');
                $table->integer('total_rest')->nullable()->after('overtime');
                $table->integer('clock_in_out')->nullable()->default(0)->after('total_rest');
                $table->string('clock_in_ip')->nullable()->after('clock_in_out');
                $table->string('clock_out_ip')->nullable()->after('clock_in_ip');
                $table->unsignedBigInteger('user_id')->nullable()->after('clock_out_ip');

                // Add foreign key for company if business table exists
                if (Schema::hasTable('business')) {
                    $table->foreign('company_id')->references('id')->on('business')->onDelete('set null');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                // Drop foreign key first if it exists
                $table->dropForeignIfExists('attendances_company_id_foreign');
                
                // Drop the columns we added
                $columns = ['company_id', 'total_work', 'late_time', 'depart_early', 'overtime', 
                           'total_rest', 'clock_in_out', 'clock_in_ip', 'clock_out_ip', 'user_id'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('attendances', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
}
