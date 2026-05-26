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
        if (! Schema::hasTable('bank_reconciliation_audit_logs')) {
            Schema::create('bank_reconciliation_audit_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('run_id')->index();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('user_id')->nullable()->index();
                $table->string('action', 60)->index();
                $table->string('entity_type', 40)->nullable()->index();
                $table->unsignedBigInteger('entity_id')->nullable()->index();
                $table->json('before_data')->nullable();
                $table->json('after_data')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('created_at')->nullable();
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
        if (Schema::hasTable('bank_reconciliation_audit_logs')) {
            Schema::drop('bank_reconciliation_audit_logs');
        }
    }
};
