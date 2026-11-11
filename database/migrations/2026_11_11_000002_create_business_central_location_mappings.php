<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('business_central_location_mappings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('pos_location_id');
            $table->string('bc_location_code', 20);
            $table->string('bc_location_id', 100)->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_inventory_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique('pos_location_id', 'bc_location_pos_unique');
            $table->index(['business_id', 'enabled'], 'bc_location_business_enabled_index');
        });

        $settings = DB::table('admin_settings')->first();
        if (! empty($settings?->business_central_business_id)
            && ! empty($settings?->business_central_location_id)
            && ! empty($settings?->business_central_location_code)) {
            DB::table('business_central_location_mappings')->insert([
                'business_id' => $settings->business_central_business_id,
                'pos_location_id' => $settings->business_central_location_id,
                'bc_location_code' => $settings->business_central_location_code,
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('business_central_location_mappings');
    }
};
