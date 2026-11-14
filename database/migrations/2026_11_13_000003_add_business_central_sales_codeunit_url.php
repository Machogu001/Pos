<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->string('business_central_sales_codeunit_url', 500)
                ->nullable()
                ->after('business_central_salespeople_url');
        });
    }

    public function down()
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->dropColumn('business_central_sales_codeunit_url');
        });
    }
};
