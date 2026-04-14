<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('holidays')) {
            return;
        }

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'start_date', 'end_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('holidays');
    }
};