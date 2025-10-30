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
        Schema::table('stocktakes', function (Blueprint $table) {
            if (!Schema::hasColumn('stocktakes', 'completed_by')) {
                $table->unsignedInteger('completed_by')
                      ->nullable()
                      ->after('status')
                      ->comment('User ID who completed the stocktake');
                
                // Optional: Add foreign key constraint if needed
                // $table->foreign('completed_by')
                //       ->references('id')
                //       ->on('users')
                //       ->onDelete('set null');
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
        Schema::table('stocktakes', function (Blueprint $table) {
            if (Schema::hasColumn('stocktakes', 'completed_by')) {
                // Drop foreign key first if you added it
                // $table->dropForeign(['completed_by']);
                
                $table->dropColumn('completed_by');
            }
        });
    }
};