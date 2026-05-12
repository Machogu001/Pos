<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_unit_conversions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('unit_id');
            $table->decimal('qty_per_base', 20, 4)->default(1);
            $table->boolean('is_purchase_default')->default(false);
            $table->boolean('is_sale_default')->default(false);
            $table->timestamps();

            $table->unique(['business_id', 'product_id', 'unit_id'], 'puc_business_product_unit_unique');
            $table->index(['business_id', 'product_id'], 'puc_business_product_index');

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_unit_conversions');
    }
};
