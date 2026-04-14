<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        Schema::table('admin_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_settings', 'payroll_expense_account_id')) {
                $table->unsignedBigInteger('payroll_expense_account_id')->nullable()->after('recent_limit');
            }

            if (! Schema::hasColumn('admin_settings', 'payroll_clearing_account_id')) {
                $table->unsignedBigInteger('payroll_clearing_account_id')->nullable()->after('payroll_expense_account_id');
            }

            if (! Schema::hasColumn('admin_settings', 'payroll_auto_post')) {
                $table->boolean('payroll_auto_post')->default(true)->after('payroll_clearing_account_id');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        Schema::table('admin_settings', function (Blueprint $table) {
            $columns = ['payroll_expense_account_id', 'payroll_clearing_account_id', 'payroll_auto_post'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('admin_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};