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
            return;
        }

        Schema::create('bank_reconciliation_runs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('business_id')->unsigned();
            $table->integer('user_id')->unsigned()->nullable();
            $table->integer('account_id')->unsigned()->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('statement_filename')->nullable();
            $table->integer('total_statement_lines')->default(0);
            $table->decimal('total_statement_amount', 22, 4)->default(0);
            $table->integer('matched_count')->default(0);
            $table->integer('ambiguous_count')->default(0);
            $table->integer('unmatched_count')->default(0);
            $table->integer('invalid_count')->default(0);
            $table->decimal('total_matched_amount', 22, 4)->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'created_at'], 'br_runs_business_created_idx');
            $table->index(['account_id', 'created_at'], 'br_runs_account_created_idx');
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

        Schema::drop('bank_reconciliation_runs');
    }
};
