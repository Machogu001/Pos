<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('admin_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('monthly_price', 10, 2)->default(500);
            $table->decimal('quarterly_price', 10, 2)->default(1350);
            $table->decimal('yearly_price', 10, 2)->default(4800);
            $table->boolean('auto_renewal')->default(true);
            $table->integer('grace_period_days')->default(7);
            $table->timestamps();
        });
        
        // Insert default settings
        DB::table('admin_settings')->insert([
            'monthly_price' => 500,
            'quarterly_price' => 1350,
            'yearly_price' => 4800,
            'auto_renewal' => true,
            'grace_period_days' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('admin_settings');
    }
};