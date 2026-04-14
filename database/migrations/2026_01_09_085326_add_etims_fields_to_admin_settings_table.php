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
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        Schema::table('admin_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_settings', 'etims_api_url')) {
                $table->string('etims_api_url', 500)->nullable()->after('subscription_round_precision');
            }
            if (! Schema::hasColumn('admin_settings', 'etims_api_token')) {
                $table->text('etims_api_token')->nullable()->after('etims_api_url');
            }
            if (! Schema::hasColumn('admin_settings', 'etims_branch_id')) {
                $table->string('etims_branch_id', 10)->nullable()->after('etims_api_token');
            }
            if (! Schema::hasColumn('admin_settings', 'etims_auto_transmit')) {
                $table->boolean('etims_auto_transmit')->default(false)->after('etims_branch_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        Schema::table('admin_settings', function (Blueprint $table) {
            $columns = [];
            foreach (['etims_api_url', 'etims_api_token', 'etims_branch_id', 'etims_auto_transmit'] as $column) {
                if (Schema::hasColumn('admin_settings', $column)) {
                    $columns[] = $column;
                }
            }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
