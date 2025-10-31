<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayrollSettingsToAdminSettings extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        Schema::table('admin_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_settings', 'payroll_nssf_percent')) {
                $table->decimal('payroll_nssf_percent', 8, 5)->nullable()->default(0.0048)->after('recent_limit');
            }
            if (! Schema::hasColumn('admin_settings', 'payroll_shif_percent')) {
                $table->decimal('payroll_shif_percent', 8, 5)->nullable()->default(0.0275)->after('payroll_nssf_percent');
            }
            if (! Schema::hasColumn('admin_settings', 'payroll_housing_percent')) {
                $table->decimal('payroll_housing_percent', 8, 5)->nullable()->default(0.015)->after('payroll_shif_percent');
            }
            if (! Schema::hasColumn('admin_settings', 'payroll_tax_percent')) {
                $table->decimal('payroll_tax_percent', 8, 5)->nullable()->default(0.245)->after('payroll_housing_percent');
            }
            if (! Schema::hasColumn('admin_settings', 'payroll_personal_relief')) {
                $table->decimal('payroll_personal_relief', 15, 2)->nullable()->default(2400)->after('payroll_tax_percent');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        Schema::table('admin_settings', function (Blueprint $table) {
            $cols = ['payroll_nssf_percent','payroll_shif_percent','payroll_housing_percent','payroll_tax_percent','payroll_personal_relief'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('admin_settings', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
}
