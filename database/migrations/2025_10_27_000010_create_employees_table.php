<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('firstname');
            $table->string('lastname')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('country')->nullable();
            $table->string('gender')->nullable();
            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->unsignedBigInteger('office_shift_id')->nullable();
            $table->date('joining_date')->nullable();
            $table->date('leaving_date')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('zipcode')->nullable();
            $table->text('address')->nullable();
            $table->decimal('basic_salary', 15, 2)->nullable();
            $table->decimal('hourly_rate', 15, 2)->nullable();
            $table->integer('remaining_leave')->default(0);
            $table->integer('total_leave')->default(0);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            // foreign keys if referenced tables exist
            // Schema will ignore if referenced table doesn't exist at migration time
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
};
