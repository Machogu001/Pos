<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmployeeAndNetColumnsToHrmPayrollsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('hrm_payrolls')) {
            Schema::table('hrm_payrolls', function (Blueprint $table) {
                // Link each payroll row to an employee (if using per-employee records)
                if (! Schema::hasColumn('hrm_payrolls', 'employee_id')) {
                    $table->unsignedBigInteger('employee_id')->nullable()->after('company_id');
                }

                // Optional detailed period range
                if (! Schema::hasColumn('hrm_payrolls', 'period_start')) {
                    $table->date('period_start')->nullable()->after('year');
                }
                if (! Schema::hasColumn('hrm_payrolls', 'period_end')) {
                    $table->date('period_end')->nullable()->after('period_start');
                }

                // Per-record gross, deductions and net (used by controllers/UI)
                if (! Schema::hasColumn('hrm_payrolls', 'gross')) {
                    $table->decimal('gross', 15, 2)->default(0)->after('basic_pay');
                }
                if (! Schema::hasColumn('hrm_payrolls', 'deductions')) {
                    $table->decimal('deductions', 15, 2)->default(0)->after('pay_after_tax');
                }
                if (! Schema::hasColumn('hrm_payrolls', 'net')) {
                    $table->decimal('net', 15, 2)->default(0)->after('deductions');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('hrm_payrolls')) {
            Schema::table('hrm_payrolls', function (Blueprint $table) {
                if (Schema::hasColumn('hrm_payrolls', 'employee_id')) {
                    $table->dropColumn('employee_id');
                }
                if (Schema::hasColumn('hrm_payrolls', 'period_start')) {
                    $table->dropColumn('period_start');
                }
                if (Schema::hasColumn('hrm_payrolls', 'period_end')) {
                    $table->dropColumn('period_end');
                }
                if (Schema::hasColumn('hrm_payrolls', 'gross')) {
                    $table->dropColumn('gross');
                }
                if (Schema::hasColumn('hrm_payrolls', 'deductions')) {
                    $table->dropColumn('deductions');
                }
                if (Schema::hasColumn('hrm_payrolls', 'net')) {
                    $table->dropColumn('net');
                }
            });
        }
    }
}
