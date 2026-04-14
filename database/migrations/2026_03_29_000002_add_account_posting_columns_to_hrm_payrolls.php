<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('hrm_payrolls')) {
            return;
        }

        Schema::table('hrm_payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('hrm_payrolls', 'posted_to_accounts')) {
                $table->boolean('posted_to_accounts')->default(false)->after('net');
            }

            if (! Schema::hasColumn('hrm_payrolls', 'posted_at')) {
                $table->dateTime('posted_at')->nullable()->after('posted_to_accounts');
            }

            if (! Schema::hasColumn('hrm_payrolls', 'debit_account_transaction_id')) {
                $table->unsignedBigInteger('debit_account_transaction_id')->nullable()->after('posted_at');
            }

            if (! Schema::hasColumn('hrm_payrolls', 'credit_account_transaction_id')) {
                $table->unsignedBigInteger('credit_account_transaction_id')->nullable()->after('debit_account_transaction_id');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('hrm_payrolls')) {
            return;
        }

        Schema::table('hrm_payrolls', function (Blueprint $table) {
            $columns = ['posted_to_accounts', 'posted_at', 'debit_account_transaction_id', 'credit_account_transaction_id'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('hrm_payrolls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};