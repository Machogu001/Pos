<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('employee_deductions')) {
            return;
        }

        Schema::create('employee_deductions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('type')->nullable();
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_deductions');
    }
};