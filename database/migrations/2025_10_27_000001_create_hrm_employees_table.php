<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrmEmployeesTable extends Migration
{
    public function up()
    {
        Schema::create('hrm_employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->date('hire_date')->nullable();
            $table->decimal('salary', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('hrm_departments')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hrm_employees');
    }
}
