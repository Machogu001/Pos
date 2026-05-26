<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('bank_reconciliation_runs')) {
            return;
        }

        Schema::table('bank_reconciliation_runs', function (Blueprint $table) {
            if (! Schema::hasColumn('bank_reconciliation_runs', 'status')) {
                $table->string('status', 20)->default('completed')->after('total_matched_amount');
                $table->index(['business_id', 'status'], 'br_runs_business_status_idx');
            }

            if (! Schema::hasColumn('bank_reconciliation_runs', 'finalized_at')) {
                $table->timestamp('finalized_at')->nullable()->after('completed_at');
            }

            if (! Schema::hasColumn('bank_reconciliation_runs', 'finalized_by')) {
                $table->integer('finalized_by')->unsigned()->nullable()->after('finalized_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('bank_reconciliation_runs')) {
            return;
        }

        Schema::table('bank_reconciliation_runs', function (Blueprint $table) {
            if (Schema::hasColumn('bank_reconciliation_runs', 'finalized_by')) {
                $table->dropColumn('finalized_by');
            }

            if (Schema::hasColumn('bank_reconciliation_runs', 'finalized_at')) {
                $table->dropColumn('finalized_at');
            }

            if (Schema::hasColumn('bank_reconciliation_runs', 'status')) {
                $table->dropIndex('br_runs_business_status_idx');
                $table->dropColumn('status');
            }
        });
    }
};
