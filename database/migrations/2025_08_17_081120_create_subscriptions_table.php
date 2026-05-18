<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('subscriptions')) {
            // Table already created by Superadmin module — add any missing columns
            Schema::table('subscriptions', function (Blueprint $table) {
                if (! Schema::hasColumn('subscriptions', 'user_id')) {
                    $table->unsignedInteger('user_id')->nullable()->after('id');
                }
                if (! Schema::hasColumn('subscriptions', 'plan_name')) {
                    $table->string('plan_name')->nullable()->after('user_id');
                }
                if (! Schema::hasColumn('subscriptions', 'billing_cycle')) {
                    $table->string('billing_cycle')->nullable()->after('plan_name');
                }
                if (! Schema::hasColumn('subscriptions', 'amount')) {
                    $table->decimal('amount', 10, 2)->nullable()->after('billing_cycle');
                }
                if (! Schema::hasColumn('subscriptions', 'mpesa_receipt')) {
                    $table->string('mpesa_receipt')->nullable();
                }
                if (! Schema::hasColumn('subscriptions', 'checkout_request_id')) {
                    $table->string('checkout_request_id')->nullable();
                }
            });
            return;
        }
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            
            // Use unsignedInteger to match users.id (which is INT UNSIGNED)
            $table->unsignedInteger('user_id');
            
            $table->string('plan_name');
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'yearly']);
            $table->decimal('amount', 10, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['active', 'expired', 'canceled', 'pending'])->default('active');
            $table->string('mpesa_receipt')->nullable();
            $table->string('checkout_request_id')->nullable();
            $table->timestamps();

            // Add foreign key constraint manually
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
};
