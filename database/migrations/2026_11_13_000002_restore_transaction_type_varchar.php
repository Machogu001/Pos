<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE transactions MODIFY COLUMN type VARCHAR(191) DEFAULT NULL');
        }

        DB::table('transactions')
            ->where('additional_notes', 'Business Central inventory bridge')
            ->where(function ($query) {
                $query->whereNull('type')->orWhere('type', '');
            })
            ->update(['type' => 'opening_stock']);
    }

    public function down()
    {
    }
};
