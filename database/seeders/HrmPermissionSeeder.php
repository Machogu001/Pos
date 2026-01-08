<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class HrmPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the hrm.access permission exists
        if (! Permission::where('name', 'hrm.access')->where('guard_name', 'web')->exists()) {
            Permission::create(['name' => 'hrm.access', 'guard_name' => 'web']);
        }
    }
}
