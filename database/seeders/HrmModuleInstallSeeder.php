<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\System;

class HrmModuleInstallSeeder extends Seeder
{
    public function run(): void
    {
        // Mark HRM module as installed so ModuleUtil includes it
        System::addProperty('hrm_version', config('hrm.module_version', '1.0.0'));
    }
}
