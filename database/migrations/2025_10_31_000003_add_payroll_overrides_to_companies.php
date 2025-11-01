<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayrollOverridesToCompanies extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'nssf_percent')) {
                $table->decimal('nssf_percent', 8, 5)->nullable()->after('business_id');
            }
            if (! Schema::hasColumn('companies', 'shif_percent')) {
                $table->decimal('shif_percent', 8, 5)->nullable()->after('nssf_percent');
            }
            if (! Schema::hasColumn('companies', 'housing_percent')) {
                $table->decimal('housing_percent', 8, 5)->nullable()->after('shif_percent');
            }
            if (! Schema::hasColumn('companies', 'tax_percent')) {
                $table->decimal('tax_percent', 8, 5)->nullable()->after('housing_percent');
            }
            if (! Schema::hasColumn('companies', 'personal_relief')) {
                $table->decimal('personal_relief', 15, 2)->nullable()->after('tax_percent');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $cols = ['nssf_percent','shif_percent','housing_percent','tax_percent','personal_relief'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('companies', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
}
