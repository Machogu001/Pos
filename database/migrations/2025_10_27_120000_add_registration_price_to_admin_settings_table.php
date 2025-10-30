<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRegistrationPriceToAdminSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('admin_settings')) {
            Schema::table('admin_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('admin_settings', 'registration_price')) {
                    $table->decimal('registration_price', 10, 2)->default(5)->after('yearly_price');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('admin_settings')) {
            Schema::table('admin_settings', function (Blueprint $table) {
                if (Schema::hasColumn('admin_settings', 'registration_price')) {
                    $table->dropColumn('registration_price');
                }
            });
        }
    }
}
