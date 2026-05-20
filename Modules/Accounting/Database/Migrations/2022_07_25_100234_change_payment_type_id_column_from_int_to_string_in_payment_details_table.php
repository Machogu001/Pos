<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangePaymentTypeIdColumnFromIntToStringInPaymentDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('payment_details') || ! Schema::hasColumn('payment_details', 'payment_type_id')) {
            return;
        }

        DB::statement("ALTER TABLE `payment_details` CHANGE `payment_type_id` `payment_type_id` VARCHAR(11) NULL DEFAULT NULL;");
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('payment_details') || ! Schema::hasColumn('payment_details', 'payment_type_id')) {
            return;
        }

        DB::statement("ALTER TABLE `payment_details` CHANGE `payment_type_id` `payment_type_id` INT(11) NULL DEFAULT NULL;");
    }
}