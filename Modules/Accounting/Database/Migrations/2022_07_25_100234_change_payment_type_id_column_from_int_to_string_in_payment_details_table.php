<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ChangePaymentTypeIdColumnFromIntToStringInPaymentDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
        DB::statement("ALTER TABLE `payment_details` CHANGE `payment_type_id` `payment_type_id` VARCHAR(11) NULL DEFAULT NULL;");
        }
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
        DB::statement("ALTER TABLE `payment_details` CHANGE `payment_type_id` `payment_type_id` INT(11) NULL DEFAULT NULL;");
        }
    }
}