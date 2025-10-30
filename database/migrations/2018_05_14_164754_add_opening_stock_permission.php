<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::firstOrCreate(
            ['name' => 'product.opening_stock', 'guard_name' => 'web']
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where('name', 'product.opening_stock')
            ->where('guard_name', 'web')
            ->delete();
    }
};
