<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class AddPayComponentsToHrmPayrolls extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('hrm_payrolls')) {
            return;
        }

        Schema::table('hrm_payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('hrm_payrolls', 'basic_pay')) {
                $table->decimal('basic_pay', 15, 2)->default(0)->after('period_end');
            }
            if (! Schema::hasColumn('hrm_payrolls', 'nssf')) {
                $table->decimal('nssf', 15, 2)->default(0)->after('basic_pay');
            }
            if (! Schema::hasColumn('hrm_payrolls', 'shif')) {
                $table->decimal('shif', 15, 2)->default(0)->after('nssf');
            }
            if (! Schema::hasColumn('hrm_payrolls', 'housing_levy')) {
                $table->decimal('housing_levy', 15, 2)->default(0)->after('shif');
            }
            if (! Schema::hasColumn('hrm_payrolls', 'taxable_pay')) {
                $table->decimal('taxable_pay', 15, 2)->default(0)->after('housing_levy');
            }
            if (! Schema::hasColumn('hrm_payrolls', 'income_tax')) {
                $table->decimal('income_tax', 15, 2)->default(0)->after('taxable_pay');
            }
            if (! Schema::hasColumn('hrm_payrolls', 'personal_relief')) {
                $table->decimal('personal_relief', 15, 2)->default(0)->after('income_tax');
            }
            if (! Schema::hasColumn('hrm_payrolls', 'paye')) {
                $table->decimal('paye', 15, 2)->default(0)->after('personal_relief');
            }
            if (! Schema::hasColumn('hrm_payrolls', 'pay_after_tax')) {
                $table->decimal('pay_after_tax', 15, 2)->default(0)->after('paye');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('hrm_payrolls')) {
            return;
        }

        Schema::table('hrm_payrolls', function (Blueprint $table) {
            $cols = ['basic_pay','nssf','shif','housing_levy','taxable_pay','income_tax','personal_relief','paye','pay_after_tax'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('hrm_payrolls', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
}
