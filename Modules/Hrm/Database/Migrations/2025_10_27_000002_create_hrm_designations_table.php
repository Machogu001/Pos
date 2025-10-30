<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrmDesignationsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('designations')) {
            Schema::create('designations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->string('designation');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('designations');
    }
}
