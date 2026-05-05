<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

class SetupManufacturingModule extends Migration
{
    public function up()
    {
        // Manufacturing recipes (bill of materials)
        if (! Schema::hasTable('manufacturing_recipes')) {
            Schema::create('manufacturing_recipes', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('product_id')->index();      // finished product
                $table->unsignedInteger('variation_id')->nullable();
                $table->decimal('quantity', 22, 4)->default(1);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // Recipe ingredients
        if (! Schema::hasTable('manufacturing_recipe_ingredients')) {
            Schema::create('manufacturing_recipe_ingredients', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('recipe_id')->index();
                $table->unsignedInteger('ingredient_product_id')->index();
                $table->unsignedInteger('ingredient_variation_id')->nullable();
                $table->decimal('quantity', 22, 4)->default(1);
                $table->timestamps();
            });
        }

        // Production orders
        if (! Schema::hasTable('manufacturing_productions')) {
            Schema::create('manufacturing_productions', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('location_id')->nullable();
                $table->unsignedInteger('recipe_id')->index();
                $table->decimal('quantity_produced', 22, 4)->default(1);
                $table->string('status', 30)->default('pending'); // pending, in_progress, completed
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamp('produced_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // Register permissions
        $permissions = [
            'manufacturing.view_recipe',
            'manufacturing.create_recipe',
            'manufacturing.edit_recipe',
            'manufacturing.delete_recipe',
            'manufacturing.view_production',
            'manufacturing.create_production',
        ];

        foreach ($permissions as $permission) {
            if (! Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('manufacturing_productions');
        Schema::dropIfExists('manufacturing_recipe_ingredients');
        Schema::dropIfExists('manufacturing_recipes');
    }
}
