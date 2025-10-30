<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrmAttendanceTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->date('date');
                $table->time('clock_in')->nullable();
                $table->time('clock_out')->nullable();
                $table->integer('duration_minutes')->nullable();
                $table->string('status')->nullable();
                $table->softDeletes();
                $table->timestamps();

                if (Schema::hasTable('employees')) {
                    $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                }
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}
