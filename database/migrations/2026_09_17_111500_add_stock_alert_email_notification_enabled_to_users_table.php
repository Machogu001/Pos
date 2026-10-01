<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'stock_alert_email_notification_enabled')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('stock_alert_email_notification_enabled')->default(true)->after('stock_alert_sms_notification_enabled');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'stock_alert_email_notification_enabled')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('stock_alert_email_notification_enabled');
        });
    }
};