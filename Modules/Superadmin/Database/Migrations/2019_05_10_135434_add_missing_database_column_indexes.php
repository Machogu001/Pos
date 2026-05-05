<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddMissingDatabaseColumnIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('subscriptions')) {
            return;
        }

        if (Schema::hasColumn('subscriptions', 'package_id') && ! $this->hasIndexForColumn('subscriptions', 'package_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->index('package_id', 'subscriptions_package_id_index');
            });
        }

        if (Schema::hasColumn('subscriptions', 'created_id') && ! $this->hasIndexForColumn('subscriptions', 'created_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->index('created_id', 'subscriptions_created_id_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }

    private function hasIndexForColumn(string $table, string $column): bool
    {
        $databaseName = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $databaseName)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->exists();
    }
}
