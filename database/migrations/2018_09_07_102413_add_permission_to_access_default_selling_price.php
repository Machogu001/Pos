<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create or fetch the permission safely
        $permission = Permission::firstOrCreate(
            ['name' => 'access_default_selling_price', 'guard_name' => 'web']
        );

        // Assign to all existing roles
        $roles = Role::all();
        foreach ($roles as $role) {
            if (!$role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove the permission from all roles
        $roles = Role::all();
        foreach ($roles as $role) {
            if ($role->hasPermissionTo('access_default_selling_price')) {
                $role->revokePermissionTo('access_default_selling_price');
            }
        }

        // Delete the permission itself
        Permission::where('name', 'access_default_selling_price')
            ->where('guard_name', 'web')
            ->delete();
    }
};
