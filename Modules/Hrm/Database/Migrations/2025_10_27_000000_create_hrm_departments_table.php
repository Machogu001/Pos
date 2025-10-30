<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrmDepartmentsTable extends Migration
{
    public function up()
    {
        // Create a standard `departments` table if it doesn't already exist.
        // This aligns table/column names with the module's models and controller.
        if (! Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                // Column name expected by Department model and controllers
                $table->string('department');
                $table->unsignedBigInteger('department_head')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->text('description')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('hrm_departments');
    }
}
