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
        Schema::table('purchase_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_lines', 'location_id')) {
                $table->unsignedBigInteger('location_id')->nullable()->after('variation_id');

                // If you have a locations table and want to enforce referential integrity:
                // $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');
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
        Schema::table('purchase_lines', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_lines', 'location_id')) {
                // If you added a foreign key in `up()`, you must drop it before dropping the column:
                // $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            }
        });
    }
};
