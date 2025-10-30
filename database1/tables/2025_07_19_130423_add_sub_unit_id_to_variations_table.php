<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('variations', function (Blueprint $table) {
            // Skip if column already exists
            if (Schema::hasColumn('variations', 'sub_unit_id')) {
                return;
            }

            // Add the column with the safest default type
            $table->unsignedBigInteger('sub_unit_id')
                  ->nullable()
                  ->after('default_sell_price')
                  ->comment('Reference to sub-unit of measurement');

            // Only add foreign key if units table exists
            if (Schema::hasTable('units')) {
                try {
                    // Verify the units.id column exists
                    if (Schema::hasColumn('units', 'id')) {
                        $table->foreign('sub_unit_id')
                              ->references('id')
                              ->on('units')
                              ->onDelete('set null');
                    }
                } catch (\Exception $e) {
                    // Log error but don't fail migration
                    \Log::error('Failed to add foreign key: '.$e->getMessage());
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('variations', function (Blueprint $table) {
            // First check if foreign key exists
            $foreignKeys = Schema::getConnection()
                               ->getDoctrineSchemaManager()
                               ->listTableForeignKeys($table->getTable());

            $fkExists = collect($foreignKeys)->contains(function ($fk) {
                return in_array('sub_unit_id', $fk->getLocalColumns());
            });

            if ($fkExists) {
                $table->dropForeign(['sub_unit_id']);
            }

            // Then drop column if exists
            if (Schema::hasColumn('variations', 'sub_unit_id')) {
                $table->dropColumn('sub_unit_id');
            }
        });
    }
};