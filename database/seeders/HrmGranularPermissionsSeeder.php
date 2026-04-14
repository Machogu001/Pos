<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class HrmGranularPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $perms = [
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
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate([
                'name' => $p,
                'guard_name' => 'web',
            ]);
        }
    }
}
