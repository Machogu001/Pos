<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('products')
            ->whereNull('type')
            ->update(['type' => 'single', 'updated_at' => now()]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Optional: revert 'single' back to null
        DB::table('products')
            ->where('type', 'single')
            ->update(['type' => null, 'updated_at' => now()]);
    }
};
