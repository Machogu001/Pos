<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->string('subscription_mpesa_store_number', 20)
                ->nullable()
                ->default(null)
                ->after('subscription_mpesa_shortcode_type')
                ->comment('For Till (Buy Goods) STK Push: PartyB store/head-office number');
        });
    }

    public function down(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->dropColumn('subscription_mpesa_store_number');
        });
    }
};
