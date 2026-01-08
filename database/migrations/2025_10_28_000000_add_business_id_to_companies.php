<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        if (Schema::hasTable('companies') && !Schema::hasColumn('companies', 'business_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->unsignedBigInteger('business_id')->nullable()->after('id')->index();
                // Not adding foreign key to avoid migration issues across installs
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
        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'business_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('business_id');
            });
        }
    }
};
