<?php

namespace Modules\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class HrmPermissionsSeeder extends Seeder
{
    /**
     * Canonical HRM permission scheme: hrm.<resource>.
     * Legacy 'resource.action' entries are kept alongside for backward compatibility
     * because existing policies check both (via OR conditions).
     */
    public function run(): void
    {
        $permissions = [
            // Feature-level toggling (coarse-grained — grants access to the section)
            'hrm.access',
            'hrm.companies',
            'hrm.departments',
            'hrm.designations',
            'hrm.office_shifts',
            'hrm.employees',
            'hrm.payrolls',
            'hrm.leaves',
            'hrm.attendances',
            'hrm.holidays',
            'hrm.settings',

            // Fine-grained CRUD — used by Laravel policies (authorizeForUser)
            'employee.view', 'employee.create', 'employee.update', 'employee.delete',
            'leave.view', 'leave.create', 'leave.update', 'leave.delete',
            'payroll.view', 'payroll.create', 'payroll.update', 'payroll.delete',
            'attendance.view', 'attendance.create', 'attendance.update', 'attendance.delete',
            'holiday.view', 'holiday.create', 'holiday.update', 'holiday.delete',
        ];

        $guardName = config('auth.defaults.guard', 'web');
        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name'       => $perm,
                'guard_name' => $guardName,
            ]);
        }

        // Assign all HRM permissions to every Admin role
        $adminRoles       = Role::where('name', 'like', 'Admin%')->get();
        $permissionModels = Permission::whereIn('name', $permissions)->get();
        foreach ($adminRoles as $role) {
            $role->syncPermissions($permissionModels->merge($role->permissions));
        }
    }
}
