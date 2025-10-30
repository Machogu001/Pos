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
        Schema::table('oauth_clients', function (Blueprint $table) {
            // Check if column doesn't exist before adding it
            if (!Schema::hasColumn('oauth_clients', 'provider')) {
                $table->string('provider')->after('secret')->nullable();
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
        Schema::table('oauth_clients', function (Blueprint $table) {
            // Only drop the column if it exists
            if (Schema::hasColumn('oauth_clients', 'provider')) {
                $table->dropColumn('provider');
            }
        });
    }
};