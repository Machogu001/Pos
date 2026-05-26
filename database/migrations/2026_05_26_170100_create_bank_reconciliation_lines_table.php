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
        if (Schema::hasTable('bank_reconciliation_lines')) {
            return;
        }

        Schema::create('bank_reconciliation_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('run_id');
            $table->integer('line_no')->nullable();
            $table->date('statement_date')->nullable();
            $table->decimal('statement_amount', 22, 4)->nullable();
            $table->string('description')->nullable();
            $table->string('reference')->nullable();
            $table->enum('status', ['matched', 'ambiguous', 'unmatched', 'invalid'])->default('unmatched');
            $table->integer('matched_transaction_payment_id')->unsigned()->nullable();
            $table->text('candidate_payment_ids')->nullable();
            $table->timestamps();

            $table->foreign('run_id', 'br_lines_run_fk')
                ->references('id')
                ->on('bank_reconciliation_runs')
                ->onDelete('cascade');

            $table->index(['run_id', 'status'], 'br_lines_run_status_idx');
            $table->index('matched_transaction_payment_id', 'br_lines_matched_payment_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('bank_reconciliation_lines')) {
            return;
        }

        Schema::drop('bank_reconciliation_lines');
    }
};
