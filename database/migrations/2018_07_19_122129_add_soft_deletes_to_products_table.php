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
        Schema::table('products', function (Blueprint $table) {
            // Check if column doesn't exist before adding it
            if (!Schema::hasColumn('products', 'deleted_at')) {
                $table->softDeletes(); // Adds nullable deleted_at column
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            // Only drop the column if it exists
            if (Schema::hasColumn('products', 'deleted_at')) {
                $table->dropSoftDeletes(); // Removes the deleted_at column
            }
        });
    }
};