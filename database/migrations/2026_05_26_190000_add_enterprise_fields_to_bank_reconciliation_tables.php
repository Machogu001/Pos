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
        if (Schema::hasTable('bank_reconciliation_runs')) {
            Schema::table('bank_reconciliation_runs', function (Blueprint $table) {
                if (! Schema::hasColumn('bank_reconciliation_runs', 'opening_balance')) {
                    $table->decimal('opening_balance', 22, 4)->nullable()->after('total_matched_amount');
                }
                if (! Schema::hasColumn('bank_reconciliation_runs', 'closing_balance_statement')) {
                    $table->decimal('closing_balance_statement', 22, 4)->nullable()->after('opening_balance');
                }
                if (! Schema::hasColumn('bank_reconciliation_runs', 'ledger_closing_balance')) {
                    $table->decimal('ledger_closing_balance', 22, 4)->nullable()->after('closing_balance_statement');
                }
                if (! Schema::hasColumn('bank_reconciliation_runs', 'variance_amount')) {
                    $table->decimal('variance_amount', 22, 4)->nullable()->after('ledger_closing_balance');
                }
                if (! Schema::hasColumn('bank_reconciliation_runs', 'amount_tolerance')) {
                    $table->decimal('amount_tolerance', 22, 4)->default(0.01)->after('variance_amount');
                }
                if (! Schema::hasColumn('bank_reconciliation_runs', 'date_tolerance_days')) {
                    $table->unsignedInteger('date_tolerance_days')->default(3)->after('amount_tolerance');
                }
                if (! Schema::hasColumn('bank_reconciliation_runs', 'reconciliation_notes')) {
                    $table->text('reconciliation_notes')->nullable()->after('date_tolerance_days');
                }
                if (! Schema::hasColumn('bank_reconciliation_runs', 'undone_at')) {
                    $table->timestamp('undone_at')->nullable()->after('finalized_by');
                }
                if (! Schema::hasColumn('bank_reconciliation_runs', 'undone_by')) {
                    $table->unsignedInteger('undone_by')->nullable()->after('undone_at');
                }
            });
        }

        if (Schema::hasTable('bank_reconciliation_lines')) {
            Schema::table('bank_reconciliation_lines', function (Blueprint $table) {
                if (! Schema::hasColumn('bank_reconciliation_lines', 'match_type')) {
                    $table->string('match_type', 10)->default('auto')->after('status');
                }
                if (! Schema::hasColumn('bank_reconciliation_lines', 'is_duplicate')) {
                    $table->boolean('is_duplicate')->default(false)->after('candidate_payment_ids');
                }
                if (! Schema::hasColumn('bank_reconciliation_lines', 'reviewed_by')) {
                    $table->unsignedInteger('reviewed_by')->nullable()->after('is_duplicate');
                }
                if (! Schema::hasColumn('bank_reconciliation_lines', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
                }
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
        if (Schema::hasTable('bank_reconciliation_lines')) {
            Schema::table('bank_reconciliation_lines', function (Blueprint $table) {
                foreach (['reviewed_at', 'reviewed_by', 'is_duplicate', 'match_type'] as $column) {
                    if (Schema::hasColumn('bank_reconciliation_lines', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('bank_reconciliation_runs')) {
            Schema::table('bank_reconciliation_runs', function (Blueprint $table) {
                foreach ([
                    'undone_by',
                    'undone_at',
                    'reconciliation_notes',
                    'date_tolerance_days',
                    'amount_tolerance',
                    'variance_amount',
                    'ledger_closing_balance',
                    'closing_balance_statement',
                    'opening_balance',
                ] as $column) {
                    if (Schema::hasColumn('bank_reconciliation_runs', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
