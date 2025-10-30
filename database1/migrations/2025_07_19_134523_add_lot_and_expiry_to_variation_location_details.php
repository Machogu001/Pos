<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('variation_location_details', function (Blueprint $table) {
            if (!Schema::hasColumn('variation_location_details', 'lot_number')) {
                $table->string('lot_number', 255)->nullable()->after('qty_available');
            }
            
            if (!Schema::hasColumn('variation_location_details', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('lot_number');
            }
        });
    }

    public function down()
    {
        Schema::table('variation_location_details', function (Blueprint $table) {
            $table->dropColumn(['lot_number', 'expiry_date']);
        });
    }
};