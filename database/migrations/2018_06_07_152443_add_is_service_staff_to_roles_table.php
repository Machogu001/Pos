<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            // Add business_id column if it doesn't exist
            if (!Schema::hasColumn('roles', 'business_id')) {
                $table->unsignedInteger('business_id')->nullable()->after('guard_name');
            }

            // Add foreign key safely
            $table->foreign('business_id')
                  ->references('id')
                  ->on('business')
                  ->onDelete('cascade');

            // Add is_default column
            if (!Schema::hasColumn('roles', 'is_default')) {
                $table->boolean('is_default')->default(0)->after('business_id');
            }

            // Add is_service_staff column
            if (!Schema::hasColumn('roles', 'is_service_staff')) {
                $table->boolean('is_service_staff')->default(0)->after('is_default');
            }
        });
    }

    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            // Drop foreign key first
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $foreignKeys = $sm->listTableForeignKeys('roles');
            foreach ($foreignKeys as $fk) {
                if (in_array('business_id', $fk->getLocalColumns())) {
                    $table->dropForeign($fk->getName());
                }
            }

            // Drop columns
            if (Schema::hasColumn('roles', 'is_service_staff')) {
                $table->dropColumn('is_service_staff');
            }
            if (Schema::hasColumn('roles', 'is_default')) {
                $table->dropColumn('is_default');
            }
            if (Schema::hasColumn('roles', 'business_id')) {
                $table->dropColumn('business_id');
            }
        });
    }
};
