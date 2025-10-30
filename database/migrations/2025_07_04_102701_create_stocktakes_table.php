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
        // Create 'stocktakes' table
        Schema::create('stocktakes', function (Blueprint $table) {
            $table->id(); // bigint unsigned by default

            $table->unsignedInteger('business_id');   // Matches int unsigned
            $table->unsignedInteger('location_id');   // Matches int unsigned
            $table->unsignedInteger('created_by');    // Matches int unsigned

            $table->string('reference_no');
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'in_progress', 'completed'])->default('draft');
            $table->timestamps();
            $table->softDeletes(); // Adds deleted_at column for soft deletes

            // Foreign keys
            $table->foreign('business_id')->references('id')->on('business');
            $table->foreign('location_id')->references('id')->on('business_locations');
            $table->foreign('created_by')->references('id')->on('users');
        });

        // Create 'stocktake_items' table
        Schema::create('stocktake_items', function (Blueprint $table) {
            $table->id(); // bigint unsigned by default

            $table->unsignedBigInteger('stocktake_id');  // Matches stocktakes.id (bigint)
            $table->unsignedInteger('product_id');       // Matches products.id (int)
            $table->unsignedInteger('variation_id');     // Matches variations.id (int)

            $table->decimal('system_quantity', 15, 4);
            $table->decimal('counted_quantity', 15, 4);
            $table->decimal('variance', 15, 4);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Adds deleted_at column for soft deletes

            // Foreign keys
            $table->foreign('stocktake_id')->references('id')->on('stocktakes')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variation_id')->references('id')->on('variations');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stocktake_items');
        Schema::dropIfExists('stocktakes');
    }
};