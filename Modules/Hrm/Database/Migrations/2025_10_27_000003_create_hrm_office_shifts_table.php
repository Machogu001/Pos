<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrmOfficeShiftsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('office_shifts')) {
            Schema::create('office_shifts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('name');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->integer('break_minutes')->nullable();
                $table->text('notes')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('office_shifts');
    }
}
