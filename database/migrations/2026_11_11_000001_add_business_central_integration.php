<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->boolean('business_central_enabled')->default(false);
            $table->unsignedInteger('business_central_business_id')->nullable();
            $table->unsignedInteger('business_central_location_id')->nullable();
            $table->string('business_central_api_url', 500)->nullable();
            $table->string('business_central_inventory_url', 500)->nullable();
            $table->string('business_central_company_id', 100)->nullable();
            $table->string('business_central_location_code', 20)->nullable();
            $table->string('business_central_auth_type', 20)->default('basic');
            $table->string('business_central_username')->nullable();
            $table->text('business_central_secret')->nullable();
            $table->string('business_central_token_url', 500)->nullable();
            $table->string('business_central_client_id')->nullable();
            $table->string('business_central_scope', 500)->nullable();
            $table->timestamp('business_central_last_sync_at')->nullable();
            $table->timestamp('business_central_last_failed_at')->nullable();
            $table->text('business_central_last_error')->nullable();
        });

        Schema::create('business_central_mappings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->string('entity_type', 30);
            $table->unsignedBigInteger('local_id');
            $table->string('bc_id', 100)->nullable();
            $table->string('bc_number', 100)->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('last_error')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'entity_type', 'local_id'], 'bc_mapping_local_unique');
            $table->index(['business_id', 'entity_type', 'bc_number'], 'bc_mapping_number_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('business_central_mappings');

        Schema::table('admin_settings', function (Blueprint $table) {
            $table->dropColumn([
                'business_central_enabled',
                'business_central_business_id',
                'business_central_location_id',
                'business_central_api_url',
                'business_central_inventory_url',
                'business_central_company_id',
                'business_central_location_code',
                'business_central_auth_type',
                'business_central_username',
                'business_central_secret',
                'business_central_token_url',
                'business_central_client_id',
                'business_central_scope',
                'business_central_last_sync_at',
                'business_central_last_failed_at',
                'business_central_last_error',
            ]);
        });
    }
};
