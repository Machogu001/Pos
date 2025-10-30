<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HrmInitialSeeder extends Seeder
{
    public function run()
    {
        // Use the App\Models mapping (points to Modules/Hrm/Entities)
        $companyModel = '\\App\\Models\\Company';
        $employeeModel = '\\App\\Models\\Employee';

        // Create a sample company if none exists
        if ($companyModel::count() === 0) {
            $company = $companyModel::create([
                'name' => 'BreMac',
                'email' => 'admin@bremac.co.ke',
                'phone' => '0000000000',
                'country' => 'KENYA',
            ]);
        } else {
            $company = $companyModel::first();
        }

        // Create a sample employee if none exists
        if ($employeeModel::count() === 0) {
            $employeeModel::create([
                'firstname' => 'Admin',
                'lastname' => 'User',
                'username' => 'Admin User',
                'email' => 'admin@example.test',
                'country' => 'KENYA',
                'gender' => 'male',
                'phone' => '0000000000',
                'company_id' => $company->id ?? null,
                'department_id' => null,
                'designation_id' => null,
                'office_shift_id' => null,
                'joining_date' => now()->toDateString(),
                'remaining_leave' => 0,
                'total_leave' => 0,
            ]);
        }
    }
}
