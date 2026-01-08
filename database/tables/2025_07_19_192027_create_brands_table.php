<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('brands')) {
            Schema::create('brands', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();  // Required, unique brand name
                $table->string('slug')->unique(); // SEO-friendly URL
                $table->text('description')->nullable();
                $table->string('logo_path')->nullable(); // For storing logo images
                $table->string('website')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('position')->default(0); // For sorting
                $table->json('meta')->nullable(); // For additional metadata
                $table->timestamps();
                $table->softDeletes(); // Enable soft delete functionality
            });
        } // This closing bracket was missing
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('brands');
    }
};