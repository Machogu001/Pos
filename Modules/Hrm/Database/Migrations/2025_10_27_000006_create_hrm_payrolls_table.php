<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrmPayrollsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('payrolls')) {
            Schema::create('payrolls', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedSmallInteger('month')->nullable();
                $table->unsignedSmallInteger('year')->nullable();
                $table->decimal('total_gross', 15, 2)->default(0);
                $table->decimal('total_deductions', 15, 2)->default(0);
                $table->decimal('total_net', 15, 2)->default(0);
                $table->string('status')->default('draft'); // draft, posted, paid
                $table->unsignedBigInteger('created_by')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('payrolls');
    }
}
