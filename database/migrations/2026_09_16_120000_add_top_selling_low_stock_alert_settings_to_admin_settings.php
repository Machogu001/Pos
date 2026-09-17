<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        Schema::table('admin_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_enabled')) {
                $table->boolean('top_selling_low_stock_alert_enabled')->default(false)->after('stock_costing_backfill_last_run_at');
            }
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_frequency')) {
                $table->string('top_selling_low_stock_alert_frequency', 50)->default('every_thirty_minutes')->after('top_selling_low_stock_alert_enabled');
            }
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_time')) {
                $table->string('top_selling_low_stock_alert_time', 5)->nullable()->after('top_selling_low_stock_alert_frequency');
            }
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_days')) {
                $table->unsignedInteger('top_selling_low_stock_alert_days')->default(30)->after('top_selling_low_stock_alert_time');
            }
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_limit')) {
                $table->unsignedInteger('top_selling_low_stock_alert_limit')->default(5)->after('top_selling_low_stock_alert_days');
            }
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_business_id')) {
                $table->unsignedInteger('top_selling_low_stock_alert_business_id')->nullable()->after('top_selling_low_stock_alert_limit');
            }
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_send_in_app')) {
                $table->boolean('top_selling_low_stock_alert_send_in_app')->default(true)->after('top_selling_low_stock_alert_business_id');
            }
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_send_email')) {
                $table->boolean('top_selling_low_stock_alert_send_email')->default(false)->after('top_selling_low_stock_alert_send_in_app');
            }
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_send_sms')) {
                $table->boolean('top_selling_low_stock_alert_send_sms')->default(false)->after('top_selling_low_stock_alert_send_email');
            }
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_send_whatsapp')) {
                $table->boolean('top_selling_low_stock_alert_send_whatsapp')->default(false)->after('top_selling_low_stock_alert_send_sms');
            }
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_custom_emails')) {
                $table->text('top_selling_low_stock_alert_custom_emails')->nullable()->after('top_selling_low_stock_alert_send_whatsapp');
            }
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_custom_phones')) {
                $table->text('top_selling_low_stock_alert_custom_phones')->nullable()->after('top_selling_low_stock_alert_custom_emails');
            }
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_whatsapp_webhook_url')) {
                $table->string('top_selling_low_stock_alert_whatsapp_webhook_url', 500)->nullable()->after('top_selling_low_stock_alert_custom_phones');
            }
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_whatsapp_auth_header')) {
                $table->string('top_selling_low_stock_alert_whatsapp_auth_header', 100)->nullable()->after('top_selling_low_stock_alert_whatsapp_webhook_url');
            }
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_whatsapp_auth_token')) {
                $table->string('top_selling_low_stock_alert_whatsapp_auth_token', 500)->nullable()->after('top_selling_low_stock_alert_whatsapp_auth_header');
            }
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_whatsapp_phone_param')) {
                $table->string('top_selling_low_stock_alert_whatsapp_phone_param', 100)->default('phone')->after('top_selling_low_stock_alert_whatsapp_auth_token');
            }
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_whatsapp_message_param')) {
                $table->string('top_selling_low_stock_alert_whatsapp_message_param', 100)->default('message')->after('top_selling_low_stock_alert_whatsapp_phone_param');
            }
            if (! Schema::hasColumn('admin_settings', 'top_selling_low_stock_alert_last_run_at')) {
                $table->timestamp('top_selling_low_stock_alert_last_run_at')->nullable()->after('top_selling_low_stock_alert_whatsapp_message_param');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        Schema::table('admin_settings', function (Blueprint $table) {
            $columns = [
                'top_selling_low_stock_alert_enabled',
                'top_selling_low_stock_alert_frequency',
                'top_selling_low_stock_alert_time',
                'top_selling_low_stock_alert_days',
                'top_selling_low_stock_alert_limit',
                'top_selling_low_stock_alert_business_id',
                'top_selling_low_stock_alert_send_in_app',
                'top_selling_low_stock_alert_send_email',
                'top_selling_low_stock_alert_send_sms',
                'top_selling_low_stock_alert_send_whatsapp',
                'top_selling_low_stock_alert_custom_emails',
                'top_selling_low_stock_alert_custom_phones',
                'top_selling_low_stock_alert_whatsapp_webhook_url',
                'top_selling_low_stock_alert_whatsapp_auth_header',
                'top_selling_low_stock_alert_whatsapp_auth_token',
                'top_selling_low_stock_alert_whatsapp_phone_param',
                'top_selling_low_stock_alert_whatsapp_message_param',
                'top_selling_low_stock_alert_last_run_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('admin_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};