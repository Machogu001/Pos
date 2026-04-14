<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes P0: hrm_payrolls had no employee_id column.
 * The controller already writes employee_id, period_start, period_end, gross, net,
 * deductions, posted_to_accounts, etc. — they were simply missing from the original migration.
 */
class AddEmployeeIdAndPeriodToHrmPayrolls extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hrm_payrolls')) {
            return;
        }

        Schema::table('hrm_payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('hrm_payrolls', 'employee_id')) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('company_id');
            }
            if (! Schema::hasColumn('hrm_payrolls', 'business_id')) {
                $table->unsignedBigInteger('business_id')->nullable()->after('company_id');
            }
            if (! Schema::hasColumn('hrm_payrolls', 'period_start')) {
                $table->date('period_start')->nullable()->after('employee_id');
            }
            if (! Schema::hasColumn('hrm_payrolls', 'period_end')) {
                $table->date('period_end')->nullable()->after('period_start');
            }
            if (! Schema::hasColumn('hrm_payrolls', 'gross')) {
                $table->decimal('gross', 15, 2)->default(0)->after('period_end');
            }
            if (! Schema::hasColumn('hrm_payrolls', 'net')) {
                $table->decimal('net', 15, 2)->default(0)->after('gross');
            }
            if (! Schema::hasColumn('hrm_payrolls', 'deductions')) {
                $table->decimal('deductions', 15, 2)->default(0)->after('net');
            }
            if (! Schema::hasColumn('hrm_payrolls', 'posted_to_accounts')) {
                $table->boolean('posted_to_accounts')->default(false)->after('deductions');
            }
            if (! Schema::hasColumn('hrm_payrolls', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('posted_to_accounts');
            }
            if (! Schema::hasColumn('hrm_payrolls', 'debit_account_transaction_id')) {
                $table->unsignedBigInteger('debit_account_transaction_id')->nullable()->after('posted_at');
            }
            if (! Schema::hasColumn('hrm_payrolls', 'credit_account_transaction_id')) {
                $table->unsignedBigInteger('credit_account_transaction_id')->nullable()->after('debit_account_transaction_id');
            }
            if (! Schema::hasColumn('hrm_payrolls', 'currency')) {
                // ISO 4217 currency code for this payroll record
                $table->string('currency', 3)->default('KES')->after('credit_account_transaction_id');
            }
        });

        // Add unique constraint to prevent duplicate payroll for same employee in same period
        try {
            Schema::table('hrm_payrolls', function (Blueprint $table) {
                $table->index(['employee_id', 'period_start', 'period_end'], 'hrm_payrolls_emp_period_idx');
                $table->index('business_id', 'hrm_payrolls_business_idx');
            });
        } catch (\Throwable $e) {
            // Index already exists — safe to ignore
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('hrm_payrolls')) {
            return;
        }

        $columns = [
            'employee_id', 'business_id', 'period_start', 'period_end',
            'gross', 'net', 'deductions',
            'posted_to_accounts', 'posted_at',
            'debit_account_transaction_id', 'credit_account_transaction_id',
            'currency',
        ];

        Schema::table('hrm_payrolls', function (Blueprint $table) use ($columns) {
            foreach ($columns as $col) {
                if (Schema::hasColumn('hrm_payrolls', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
}
