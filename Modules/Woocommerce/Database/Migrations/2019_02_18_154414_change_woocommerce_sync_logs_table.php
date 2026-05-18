<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeWoocommerceSyncLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            // SQLite does not support MODIFY — skip (column is already nullable by default in SQLite)
            return;
        }
        DB::statement('ALTER TABLE woocommerce_sync_logs MODIFY operation_type VARCHAR(191) DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
