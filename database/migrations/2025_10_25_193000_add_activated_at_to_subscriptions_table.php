<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActivatedAtToSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('subscriptions', 'activated_at')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->timestamp('activated_at')->nullable()->after('checkout_request_id');
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
        if (Schema::hasColumn('subscriptions', 'activated_at')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('activated_at');
            });
        }
    }
}
