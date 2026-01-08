<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('office_shifts')) {
            Schema::create('office_shifts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamp('deleted_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('office_shifts');
    }
};
