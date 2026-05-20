<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the shortcode_type and store_number columns that were added to the live
 * database but were missing from the original subscription M-Pesa migration.
 * Uses hasColumn() guards so it is safe to run on installs that already have
 * these columns as well as on fresh installs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_settings', 'subscription_mpesa_shortcode_type')) {
                $table->string('subscription_mpesa_shortcode_type')
                      ->nullable()
                      ->default('paybill')
                      ->after('subscription_mpesa_passkey');
            }

            if (! Schema::hasColumn('admin_settings', 'subscription_mpesa_store_number')) {
                $table->string('subscription_mpesa_store_number')
                      ->nullable()
                      ->after('subscription_mpesa_shortcode_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $toDrop = [];
            if (Schema::hasColumn('admin_settings', 'subscription_mpesa_shortcode_type')) {
                $toDrop[] = 'subscription_mpesa_shortcode_type';
            }
            if (Schema::hasColumn('admin_settings', 'subscription_mpesa_store_number')) {
                $toDrop[] = 'subscription_mpesa_store_number';
            }
            if ($toDrop) {
                $table->dropColumn($toDrop);
            }
        });
    }
};
