<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
        try {
            // Add composite index for variation + location queries (most common)
            DB::statement('CREATE INDEX idx_stock_histories_variation_location ON stock_histories (variation_id, location_id, created_at)');
        } catch (\Exception $e) {
            // Index might already exist, skip
        }
        
        try {
            // Add index for adjustment type filtering
            DB::statement('CREATE INDEX idx_stock_histories_adjustment_type ON stock_histories (adjustment_type, created_at)');
        } catch (\Exception $e) {
            // Index might already exist, skip
        }
        
        try {
            // Add index for reference number lookups (if column exists)
            $columns = DB::select("SHOW COLUMNS FROM stock_histories LIKE 'reference_no'");
            if (count($columns) > 0) {
                DB::statement('CREATE INDEX idx_stock_histories_reference_no ON stock_histories (reference_no)');
            }
        } catch (\Exception $e) {
            // Index might already exist, skip
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP INDEX IF EXISTS idx_stock_histories_variation_location ON stock_histories');
        DB::statement('DROP INDEX IF EXISTS idx_stock_histories_adjustment_type ON stock_histories');
        DB::statement('DROP INDEX IF EXISTS idx_stock_histories_reference_no ON stock_histories');
    }
};
