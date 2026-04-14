<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes P0: Multi-tenant data isolation.
 * All HRM tables were missing business_id, so data from one business
 * was visible to all other businesses in this multi-business POS system.
 * Adds business_id + indexes to every HRM data table.
 */
class AddBusinessIdToHrmTables extends Migration
{
    /**
     * Tables that need business_id for tenant isolation.
     * Format: ['table' => 'anchor_column_for_after']
     */
    private array $tables = [
        'employees'    => 'company_id',
        'departments'  => 'company_id',
        'designations' => 'company_id',
        'office_shifts' => 'company_id',
        'leave_types'  => 'id',
        'attendances'  => 'employee_id',
        'holidays'     => 'id',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table => $after) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if (Schema::hasColumn($table, 'business_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $after) {
                $blueprint->unsignedBigInteger('business_id')->nullable()->after($after);
            });

            try {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $blueprint->index('business_id', "{$table}_business_id_idx");
                });
            } catch (\Throwable $e) {
                // Index already exists
            }
        }

        // Back-fill business_id on employees from the companies table when possible
        // so existing data stays queryable. Uses a raw join because this runs across
        // potentially hundreds of records.
        if (Schema::hasTable('employees') && Schema::hasTable('companies')
            && Schema::hasColumn('companies', 'business_id')
        ) {
            \DB::statement('
                UPDATE employees e
                INNER JOIN companies c ON c.id = e.company_id
                SET e.business_id = c.business_id
                WHERE e.business_id IS NULL AND c.business_id IS NOT NULL
            ');
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->tables) as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if (! Schema::hasColumn($table, 'business_id')) {
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $blueprint->dropIndex("{$table}_business_id_idx");
                });
            } catch (\Throwable $e) {
                // Index may not exist with that name
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('business_id');
            });
        }
    }
}
