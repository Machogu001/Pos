<?php

namespace Modules\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class HrmPermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            'employee.view', 'employee.create', 'employee.update', 'employee.delete',
            'leave.view', 'leave.create', 'leave.update', 'leave.delete',
            'payroll.view', 'payroll.create', 'payroll.update', 'payroll.delete',
            'attendance.view', 'attendance.create', 'attendance.update', 'attendance.delete',
        ];

        $guardName = config('auth.defaults.guard', 'web');
        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => $guardName,
            ]);
        }

        // Assign to Admin roles (roles named like Admin or Admin#<id>)
        $adminRoles = Role::where('name', 'like', 'Admin%')->get();
        $permissionModels = Permission::whereIn('name', $permissions)->get();
        foreach ($adminRoles as $role) {
            $role->syncPermissions($permissionModels);
        }
    }
}
