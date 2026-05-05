<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

class SetupConnectorModule extends Migration
{
    public function up()
    {
        // API tokens for businesses
        if (! Schema::hasTable('connector_api_tokens')) {
            Schema::create('connector_api_tokens', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('user_id')->index();
                $table->string('token', 80)->unique();
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        // API access logs
        if (! Schema::hasTable('connector_api_logs')) {
            Schema::create('connector_api_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('token_id')->nullable();
                $table->string('endpoint', 255);
                $table->string('method', 10)->default('GET');
                $table->unsignedSmallInteger('status_code')->nullable();
                $table->string('ip', 45)->nullable();
                $table->timestamp('requested_at')->useCurrent();

                $table->index('requested_at');
            });
        }

        // Register permissions
        $permissions = [
            'connector.access_api',
            'connector.manage_tokens',
        ];

        foreach ($permissions as $permission) {
            if (! Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('connector_api_logs');
        Schema::dropIfExists('connector_api_tokens');
    }
}
