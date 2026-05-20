<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddContactAndLocationIdToJournalEntriesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('journal_entries')) {
            return;
        }

        $hasClient   = Schema::hasColumn('journal_entries', 'client_id');
        $hasContact  = Schema::hasColumn('journal_entries', 'contact_id');
        $hasBranch   = Schema::hasColumn('journal_entries', 'branch_id');
        $hasLocation = Schema::hasColumn('journal_entries', 'location_id');

        Schema::table('journal_entries', function (Blueprint $table) use ($hasClient, $hasContact, $hasBranch, $hasLocation) {
            if ($hasClient && ! $hasContact) {
                $table->renameColumn('client_id', 'contact_id');
            } elseif (! $hasClient && ! $hasContact) {
                $table->unsignedInteger('contact_id')->nullable();
            }

            if ($hasBranch && ! $hasLocation) {
                $table->renameColumn('branch_id', 'location_id');
            } elseif (! $hasBranch && ! $hasLocation) {
                $table->unsignedInteger('location_id')->nullable();
            }
        });

        $this->dropIndexIfExists('journal_entries', 'client_id_index');
        $this->dropIndexIfExists('journal_entries', 'branch_id_index');
        $this->dropIndexIfExists('journal_entries', 'journal_entries_contact_id_index');
        $this->dropIndexIfExists('journal_entries', 'journal_entries_location_id_index');

        $this->addIndexIfMissing('journal_entries', 'contact_id', 'contact_id_index');
        $this->addIndexIfMissing('journal_entries', 'location_id', 'location_id_index');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('journal_entries')) {
            return;
        }

        $hasClient   = Schema::hasColumn('journal_entries', 'client_id');
        $hasContact  = Schema::hasColumn('journal_entries', 'contact_id');
        $hasBranch   = Schema::hasColumn('journal_entries', 'branch_id');
        $hasLocation = Schema::hasColumn('journal_entries', 'location_id');

        Schema::table('journal_entries', function (Blueprint $table) use ($hasClient, $hasContact, $hasBranch, $hasLocation) {
            if ($hasContact && ! $hasClient) {
                $table->renameColumn('contact_id', 'client_id');
            } elseif (! $hasContact && ! $hasClient) {
                $table->unsignedInteger('client_id')->nullable();
            }

            if ($hasLocation && ! $hasBranch) {
                $table->renameColumn('location_id', 'branch_id');
            } elseif (! $hasLocation && ! $hasBranch) {
                $table->unsignedInteger('branch_id')->nullable();
            }
        });

        $this->dropIndexIfExists('journal_entries', 'contact_id_index');
        $this->dropIndexIfExists('journal_entries', 'location_id_index');
        $this->dropIndexIfExists('journal_entries', 'journal_entries_client_id_index');
        $this->dropIndexIfExists('journal_entries', 'journal_entries_branch_id_index');

        $this->addIndexIfMissing('journal_entries', 'client_id', 'client_id_index');
        $this->addIndexIfMissing('journal_entries', 'branch_id', 'branch_id_index');
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (! $this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }

    private function addIndexIfMissing(string $tableName, string $column, string $indexName): void
    {
        if (! Schema::hasColumn($tableName, $column) || $this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($column, $indexName) {
            $table->index($column, $indexName);
        });
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $result = DB::select(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
                [$tableName, $indexName]
            );

            return ! empty($result);
        }

        if ($driver === 'sqlite') {
            $safeTable = str_replace("'", "''", $tableName);
            $indexes = DB::select("PRAGMA index_list('{$safeTable}')");

            foreach ($indexes as $index) {
                $name = is_array($index) ? ($index['name'] ?? null) : ($index->name ?? null);
                if ($name === $indexName) {
                    return true;
                }
            }
        }

        return false;
    }
}