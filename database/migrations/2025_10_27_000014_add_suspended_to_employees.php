<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (Schema::hasTable('employees') && ! Schema::hasColumn('employees', 'suspended')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->boolean('suspended')->default(false)->after('deleted_at');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'suspended')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('suspended');
            });
        }
    }
};
