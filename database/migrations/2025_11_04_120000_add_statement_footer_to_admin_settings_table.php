<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('admin_settings')) return;

        Schema::table('admin_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_settings', 'statement_footer')) {
                $table->text('statement_footer')->nullable()->after('invoice_footer');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('admin_settings')) return;

        Schema::table('admin_settings', function (Blueprint $table) {
            if (Schema::hasColumn('admin_settings', 'statement_footer')) {
                $table->dropColumn('statement_footer');
            }
        });
    }
};
