<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('product_variation_id')->nullable()->after('product_id');
            $table->decimal('old_quantity', 15, 4)->default(0)->after('created_by');
            $table->decimal('new_quantity', 15, 4)->default(0)->after('old_quantity');

            // If you want a foreign key constraint (optional):
            // $table->foreign('product_variation_id')
            //       ->references('id')->on('product_variations')
            //       ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('stock_histories', function (Blueprint $table) {
            $table->dropColumn(['product_variation_id', 'old_quantity', 'new_quantity']);
        });
    }
};
