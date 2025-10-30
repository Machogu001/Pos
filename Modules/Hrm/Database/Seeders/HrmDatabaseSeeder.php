<?php

namespace Modules\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class HrmDatabaseSeeder extends Seeder
{
    public function run()
    {
        // Seed departments
        if (\Schema::hasTable('departments')) {
            DB::table('departments')->insertOrIgnore([
                ['department' => 'Human Resources', 'department_head' => null, 'company_id' => null, 'created_at' => now(), 'updated_at' => now()],
                ['department' => 'Finance', 'department_head' => null, 'company_id' => null, 'created_at' => now(), 'updated_at' => now()],
                ['department' => 'Operations', 'department_head' => null, 'company_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // Seed designations
        if (\Schema::hasTable('designations')) {
            DB::table('designations')->insertOrIgnore([
                ['designation' => 'HR Manager', 'department_id' => 1, 'company_id' => null, 'created_at' => now(), 'updated_at' => now()],
                ['designation' => 'Accountant', 'department_id' => 2, 'company_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // Seed office shifts
        if (\Schema::hasTable('office_shifts')) {
            $shift = ['name' => 'Default Shift', 'created_at' => now(), 'updated_at' => now()];
            // Only include time/break fields if the columns exist on the table
            if (\Schema::hasColumn('office_shifts', 'start_time')) {
                $shift['start_time'] = '09:00:00';
            }
            if (\Schema::hasColumn('office_shifts', 'end_time')) {
                $shift['end_time'] = '17:00:00';
            }
            if (\Schema::hasColumn('office_shifts', 'break_minutes')) {
                $shift['break_minutes'] = 60;
            }
            DB::table('office_shifts')->insertOrIgnore([$shift]);
        }

        // Seed leave types
        if (\Schema::hasTable('leave_types')) {
            DB::table('leave_types')->insertOrIgnore([
                ['name' => 'Annual Leave', 'allowed_days' => 14, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Sick Leave', 'allowed_days' => 10, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // Note: we avoid seeding employees automatically here to prevent accidental user creation.
        // Seed permissions and attach to Admin role(s)
        try {
            if (class_exists('\\Spatie\\Permission\\Models\\Permission')) {
                $this->call(HrmPermissionsSeeder::class);
            }
        } catch (\Exception $e) {
            // non-fatal: if permission system not present, skip
        }
    }
}
