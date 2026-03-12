<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->boolean('etims_transmitted')->default(false)->after('is_suspend');
            $table->timestamp('etims_transmitted_at')->nullable()->after('etims_transmitted');
            $table->text('etims_response')->nullable()->after('etims_transmitted_at');
            $table->text('etims_error')->nullable()->after('etims_response');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['etims_transmitted', 'etims_transmitted_at', 'etims_response', 'etims_error']);
        });
    }
};
