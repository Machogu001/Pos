<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mpesa_payments') || Schema::hasColumn('mpesa_payments', 'business_id')) {
            return;
        }

        Schema::table('mpesa_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->nullable()->after('user_id');
            $table->index('business_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('mpesa_payments') || ! Schema::hasColumn('mpesa_payments', 'business_id')) {
            return;
        }

        Schema::table('mpesa_payments', function (Blueprint $table) {
            $table->dropIndex(['business_id']);
            $table->dropColumn('business_id');
        });
    }
};