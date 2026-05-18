<?php

namespace Tests\Support;

use Illuminate\Database\SQLiteConnection;

/**
 * SQLite connection that silently skips MySQL-specific ALTER TABLE clauses
 * (MODIFY, CHANGE, DROP PRIMARY KEY syntax) which SQLite does not support.
 * Used only in the test environment.
 */
class TestSQLiteConnection extends SQLiteConnection
{
    /** MySQL-specific ALTER TABLE patterns that SQLite cannot execute. */
    private const MYSQL_ALTER_PATTERNS = [
        '/ALTER\s+TABLE\s+\S+\s+MODIFY\b/i',
        '/ALTER\s+TABLE\s+`?\w+`?\s+CHANGE\b/i',
    ];

    public function statement($query, $bindings = [])
    {
        foreach (self::MYSQL_ALTER_PATTERNS as $pattern) {
            if (preg_match($pattern, $query)) {
                return true; // silently skip
            }
        }

        return parent::statement($query, $bindings);
    }
}
