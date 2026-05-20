<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('discounts')) {
            return;
        }

        if (Schema::hasColumn('discounts', 'applicable_in_spg')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE discounts DROP COLUMN applicable_in_spg');
            } else {
                Schema::table('discounts', function (Blueprint $table) {
                    $table->dropColumn('applicable_in_spg');
                });
            }
        }

        if (! Schema::hasColumn('discounts', 'spg')) {
            Schema::table('discounts', function (Blueprint $table) {
                $table->string('spg', 100)->nullable()->after('is_active')->comment('Applicable in specified selling price group only. Use of applicable_in_spg column is discontinued')->index();
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
};
