<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Base migration class that silently ignores MySQL ALTER TABLE ... MODIFY
 * statements when running under SQLite (e.g., in the test suite).
 *
 * Usage: extend this class instead of Migration in any migration that
 * contains a raw MODIFY-column statement.
 */
abstract class BaseSkipsModifyOnSqlite extends Migration
{
    /**
     * Execute a raw SQL statement, but skip it entirely on SQLite when it
     * contains a MODIFY column clause (SQLite does not support ALTER MODIFY).
     */
    protected function statementSkipModifyOnSqlite(string $sql): void
    {
        if (DB::getDriverName() === 'sqlite' && stripos($sql, 'MODIFY') !== false) {
            return;
        }
        DB::statement($sql);
    }
}
