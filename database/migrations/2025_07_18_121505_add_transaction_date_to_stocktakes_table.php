<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('stocktakes', function (Blueprint $table) {
            $table->dateTime('transaction_date')->nullable()->after('additional_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('stocktakes', function (Blueprint $table) {
            $table->dropColumn('transaction_date');
        });
    }
};