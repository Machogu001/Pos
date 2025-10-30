<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Drop trigger first to avoid "trigger already exists" error
        DB::unprepared("DROP TRIGGER IF EXISTS after_transaction_sell_line_insert");

        DB::unprepared("
            CREATE TRIGGER after_transaction_sell_line_insert
            AFTER INSERT ON transaction_sell_lines
            FOR EACH ROW
            BEGIN
                DECLARE loc_id INT;

                -- Get the location_id from the parent transaction
                SELECT location_id INTO loc_id
                FROM transactions
                WHERE id = NEW.transaction_id
                LIMIT 1;

                -- Update the qty_available based on variation_id and retrieved location_id
                UPDATE variation_location_details
                SET qty_available = qty_available - NEW.quantity
                WHERE variation_id = NEW.variation_id
                  AND location_id = loc_id;
            END
        ");
    }

    public function down()
    {
        DB::unprepared("DROP TRIGGER IF EXISTS after_transaction_sell_line_insert");
    }
};
