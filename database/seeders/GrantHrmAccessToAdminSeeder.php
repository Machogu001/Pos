<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class GrantHrmAccessToAdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRoles = Role::where('name', 'like', 'Admin#%')->get();
        foreach ($adminRoles as $role) {
            $role->givePermissionTo('hrm.access');
        }
    }
}
