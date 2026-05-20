<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('superadmin_update_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');                        // https://client.example.com
            $table->string('webhook_secret');             // raw secret for HMAC signing
            $table->string('last_version')->nullable();
            $table->enum('last_push_status', ['pending', 'success', 'failed'])->nullable();
            $table->timestamp('last_pushed_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('superadmin_update_clients');
    }
};
