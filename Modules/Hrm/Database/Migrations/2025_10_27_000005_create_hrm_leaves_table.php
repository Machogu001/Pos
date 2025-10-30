<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrmLeavesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('leaves')) {
            Schema::create('leaves', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('leave_type_id')->nullable();
                $table->date('start_date');
                $table->date('end_date');
                $table->integer('days')->default(1);
                $table->text('reason')->nullable();
                $table->string('status')->default('pending'); // pending, approved, rejected
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->softDeletes();
                $table->timestamps();

                if (Schema::hasTable('employees')) {
                    $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                }
                if (Schema::hasTable('leave_types')) {
                    $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('set null');
                }
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('leaves');
    }
}
