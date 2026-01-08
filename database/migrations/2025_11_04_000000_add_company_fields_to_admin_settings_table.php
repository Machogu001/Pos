<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('admin_settings')) return;

        Schema::table('admin_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_settings', 'company_name')) {
                $table->string('company_name')->nullable()->after('recent_limit');
            }
            if (! Schema::hasColumn('admin_settings', 'company_logo')) {
                $table->string('company_logo')->nullable()->after('company_name');
            }
            if (! Schema::hasColumn('admin_settings', 'company_contact_phone')) {
                $table->string('company_contact_phone')->nullable()->after('company_logo');
            }
            if (! Schema::hasColumn('admin_settings', 'company_contact_email')) {
                $table->string('company_contact_email')->nullable()->after('company_contact_phone');
            }
            if (! Schema::hasColumn('admin_settings', 'invoice_pin')) {
                $table->string('invoice_pin')->nullable()->after('company_contact_email');
            }
            if (! Schema::hasColumn('admin_settings', 'invoice_footer')) {
                $table->text('invoice_footer')->nullable()->after('invoice_pin');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('admin_settings')) return;

        Schema::table('admin_settings', function (Blueprint $table) {
            if (Schema::hasColumn('admin_settings', 'invoice_footer')) {
                $table->dropColumn('invoice_footer');
            }
            if (Schema::hasColumn('admin_settings', 'invoice_pin')) {
                $table->dropColumn('invoice_pin');
            }
            if (Schema::hasColumn('admin_settings', 'company_contact_email')) {
                $table->dropColumn('company_contact_email');
            }
            if (Schema::hasColumn('admin_settings', 'company_contact_phone')) {
                $table->dropColumn('company_contact_phone');
            }
            if (Schema::hasColumn('admin_settings', 'company_logo')) {
                $table->dropColumn('company_logo');
            }
            if (Schema::hasColumn('admin_settings', 'company_name')) {
                $table->dropColumn('company_name');
            }
        });
    }
};
