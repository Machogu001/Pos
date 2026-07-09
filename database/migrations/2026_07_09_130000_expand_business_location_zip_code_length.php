<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('business_locations') || ! Schema::hasColumn('business_locations', 'zip_code')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE business_locations MODIFY zip_code VARCHAR(20) NOT NULL');
    }

    public function down()
    {
        if (! Schema::hasTable('business_locations') || ! Schema::hasColumn('business_locations', 'zip_code')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE business_locations MODIFY zip_code CHAR(7) NOT NULL');
    }
};