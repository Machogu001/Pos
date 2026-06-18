<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GrantPurchaseAccessToCashierSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'purchase.view',
            'purchase.create',
            'purchase.update',
            'purchase.update_status',
            'purchase.payments',
            'stocktake.view',
            'stocktake.create',
            'stocktake.update',
            'stocktake.delete',
            'stocktake.complete',
        ];

        $permissionIds = [];
        foreach ($permissions as $permissionName) {
            $permissionId = DB::table('permissions')
                ->where('name', $permissionName)
                ->where('guard_name', 'web')
                ->value('id');

            if (! $permissionId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ]);
            }

            $permissionIds[] = $permissionId;
        }

        $cashierRoles = DB::table('roles')
            ->where('name', 'like', 'Cashier#%')
            ->get();

        foreach ($cashierRoles as $role) {
            foreach ($permissionIds as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $role->id)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permissionId,
                        'role_id' => $role->id,
                    ]);
                }
            }
        }
    }
}
