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
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->string('etims_api_url', 500)->nullable()->after('subscription_round_precision');
            $table->text('etims_api_token')->nullable()->after('etims_api_url');
            $table->string('etims_branch_id', 10)->nullable()->after('etims_api_token');
            $table->boolean('etims_auto_transmit')->default(false)->after('etims_branch_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->dropColumn(['etims_api_url', 'etims_api_token', 'etims_branch_id', 'etims_auto_transmit']);
        });
    }
};
