<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('admin_settings', 'subscription_mpesa_shortcode_type')) {
            Schema::table('admin_settings', function (Blueprint $table) {
                // 'paybill' = CustomerPayBillOnline  |  'till' = CustomerBuyGoodsOnline
                $table->string('subscription_mpesa_shortcode_type', 10)
                    ->nullable()
                    ->default('paybill')
                    ->after('subscription_mpesa_callback');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('admin_settings', 'subscription_mpesa_shortcode_type')) {
            Schema::table('admin_settings', function (Blueprint $table) {
                $table->dropColumn('subscription_mpesa_shortcode_type');
            });
        }
    }
};
