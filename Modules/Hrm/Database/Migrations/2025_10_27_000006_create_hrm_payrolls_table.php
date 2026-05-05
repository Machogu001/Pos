<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrmPayrollsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('hrm_payrolls')) {
            Schema::create('hrm_payrolls', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedSmallInteger('month')->nullable();
                $table->unsignedSmallInteger('year')->nullable();
                $table->decimal('total_gross', 15, 2)->default(0);
                $table->decimal('total_deductions', 15, 2)->default(0);
                $table->decimal('total_net', 15, 2)->default(0);
                $table->string('status')->default('draft'); // draft, posted, paid
                $table->unsignedBigInteger('created_by')->nullable();
                // Payroll component columns
                $table->decimal('basic_pay', 15, 2)->default(0);
                $table->decimal('nssf', 15, 2)->default(0);
                $table->decimal('shif', 15, 2)->default(0);
                $table->decimal('housing_levy', 15, 2)->default(0);
                $table->decimal('taxable_pay', 15, 2)->default(0);
                $table->decimal('income_tax', 15, 2)->default(0);
                $table->decimal('personal_relief', 15, 2)->default(0);
                $table->decimal('paye', 15, 2)->default(0);
                $table->decimal('pay_after_tax', 15, 2)->default(0);

                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('hrm_payrolls');
    }
}
