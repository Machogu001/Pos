<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('business_central_salespeople', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('pos_user_id')->nullable();
            $table->string('bc_id', 100);
            $table->string('code', 50);
            $table->string('display_name');
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->boolean('blocked')->default(false);
            $table->boolean('available_in_bc')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'bc_id'], 'bc_salesperson_bc_id_unique');
            $table->unique(['business_id', 'code'], 'bc_salesperson_code_unique');
            $table->unique('pos_user_id', 'bc_salesperson_pos_user_unique');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('pos_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('business_central_salespeople');
    }
};
