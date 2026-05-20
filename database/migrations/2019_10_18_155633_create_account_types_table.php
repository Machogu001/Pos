<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        if (! Schema::hasTable('account_types')) {
            Schema::create('account_types', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->integer('parent_account_type_id')->nullable();
                $table->integer('business_id');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('accounts') && ! Schema::hasColumn('accounts', 'account_type_id')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->integer('account_type_id')->nullable()->after('account_number');
            });
        }

        if (Schema::hasTable('accounts') && Schema::hasColumn('accounts', 'account_type')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE accounts DROP COLUMN account_type;');
            } else {
                Schema::table('accounts', function (Blueprint $table) {
                    $table->dropColumn('account_type');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('accounts') && Schema::hasColumn('accounts', 'account_type_id')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('account_type_id');
            });
        }

        Schema::dropIfExists('account_types');
    }
};
