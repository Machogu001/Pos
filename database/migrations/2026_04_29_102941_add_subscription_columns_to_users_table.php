<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'has_active_subscription')) {
                $table->boolean('has_active_subscription')->default(false)->after('subscription_end_date');
            }
            if (!Schema::hasColumn('users', 'subscription_expires_at')) {
                $table->timestamp('subscription_expires_at')->nullable()->after('has_active_subscription');
            }
            if (!Schema::hasColumn('users', 'subscription_status')) {
                $table->string('subscription_status', 20)->nullable()->after('subscription_expires_at');
            }
        });

        // Backfill: mark users who currently have an active subscription
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                UPDATE users u
                INNER JOIN subscriptions s ON s.user_id = u.id
                    AND s.status = 'active'
                    AND s.end_date > NOW()
                SET u.has_active_subscription = 1,
                    u.subscription_expires_at  = s.end_date,
                    u.subscription_status      = 'active'
                WHERE u.deleted_at IS NULL
            ");
        }
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = ['has_active_subscription', 'subscription_expires_at', 'subscription_status'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
