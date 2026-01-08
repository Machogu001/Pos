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
        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'invoice_sent_at')) {
                $table->timestamp('invoice_sent_at')->nullable()->after('activated_at');
            }
            if (! Schema::hasColumn('subscriptions', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('invoice_sent_at');
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
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'reminder_sent_at')) {
                $table->dropColumn('reminder_sent_at');
            }
            if (Schema::hasColumn('subscriptions', 'invoice_sent_at')) {
                $table->dropColumn('invoice_sent_at');
            }
        });
    }
};
