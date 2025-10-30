<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
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
            $table->string('checkout_request_id')->nullable()->after('mpesa_receipt');
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
