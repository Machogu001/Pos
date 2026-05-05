<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrmEmployeesTable extends Migration
{
    public function up()
    {
        // Create a standard `employees` table if it doesn't already exist.
        // The HRM controllers and models expect these column names.
        if (! Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->unsignedBigInteger('designation_id')->nullable();
                $table->unsignedBigInteger('office_shift_id')->nullable();
                $table->string('firstname');
                $table->string('lastname')->nullable();
                $table->string('username')->nullable();
                $table->string('email')->nullable()->unique();
                $table->string('gender')->nullable();
                $table->string('phone')->nullable();
                $table->date('birth_date')->nullable();
                $table->date('joining_date')->nullable();
                $table->date('leaving_date')->nullable();
                $table->integer('total_leave')->default(0);
                $table->integer('remaining_leave')->default(0);
                $table->string('marital_status')->nullable();
                $table->string('employment_type')->nullable();
                $table->string('city')->nullable();
                $table->string('province')->nullable();
                $table->string('zipcode')->nullable();
                $table->text('address')->nullable();
                $table->decimal('basic_salary', 15, 2)->nullable();
                $table->decimal('hourly_rate', 15, 2)->nullable();
                $table->string('resume')->nullable();
                $table->string('avatar')->nullable();
                $table->string('document')->nullable();
                $table->string('country')->nullable();
                $table->string('facebook')->nullable();
                $table->string('skype')->nullable();
                $table->string('whatsapp')->nullable();
                $table->string('twitter')->nullable();
                $table->string('linkedin')->nullable();
                $table->softDeletes();
                $table->timestamps();

                // Foreign keys (if the referenced tables exist)
                if (Schema::hasTable('departments')) {
                    $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
                }
                if (Schema::hasTable('designations')) {
                    $table->foreign('designation_id')->references('id')->on('designations')->onDelete('set null');
                }
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('hrm_employees');
    }
}
