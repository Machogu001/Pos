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
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id'); // Changed from unsignedBigInteger
            $table->unsignedInteger('variation_id'); // Changed from unsignedBigInteger
            $table->unsignedInteger('location_id'); // Changed from unsignedBigInteger
            $table->decimal('quantity', 15, 4)->comment('Positive for addition, negative for deduction');
            $table->string('type', 50)->comment('e.g., stock_adjustment, purchase, sale, etc.');
            $table->unsignedInteger('transaction_id')->nullable()->comment('Reference to related transaction'); // Changed
            $table->string('lot_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedInteger('created_by'); // Changed from unsignedBigInteger
            $table->unsignedInteger('updated_by')->nullable(); // Changed from unsignedBigInteger
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['product_id', 'variation_id']);
            $table->index(['location_id']);
            $table->index(['type']);
            $table->index(['created_at']);
            $table->index(['expiry_date']);

            // Foreign keys
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('restrict');

            $table->foreign('variation_id')
                  ->references('id')
                  ->on('variations')
                  ->onDelete('restrict');

            $table->foreign('location_id')
                  ->references('id')
                  ->on('business_locations')
                  ->onDelete('restrict');

            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');

            $table->foreign('updated_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('stock_histories')) {
            Schema::table('stock_histories', function (Blueprint $table) {
                // Drop foreign keys first
                $table->dropForeign(['stock_histories_product_id_foreign']);
                $table->dropForeign(['stock_histories_variation_id_foreign']);
                $table->dropForeign(['stock_histories_location_id_foreign']);
                $table->dropForeign(['stock_histories_created_by_foreign']);
                $table->dropForeign(['stock_histories_updated_by_foreign']);
            });
        }

        Schema::dropIfExists('stock_histories');
    }
};