<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GrantHrmAccessPivotSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure permission exists
        $permissionId = DB::table('permissions')
            ->where('name', 'hrm.access')
            ->where('guard_name', 'web')
            ->value('id');
        if (!$permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'hrm.access',
                'guard_name' => 'web',
            ]);
        }

        // Attach to all Admin roles
        $adminRoles = DB::table('roles')->where('name', 'like', 'Admin#%')->get();
        foreach ($adminRoles as $role) {
            $exists = DB::table('role_has_permissions')
                ->where('role_id', $role->id)
                ->where('permission_id', $permissionId)
                ->exists();
            if (!$exists) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => $role->id,
                ]);
            }
        }
    }
}
