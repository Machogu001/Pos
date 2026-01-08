<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\AdminSetting;

class AdminSettingsSeeder extends Seeder
{
    /**
     * Seed the admin_settings table with default values for fresh installations.
     *
     * @return void
     */
    public function run()
    {
        AdminSetting::firstOrCreate([], [
            'monthly_price' => 0,
            'quarterly_price' => 0,
            'yearly_price' => 0,
            'registration_price' => 0,
            'auto_renewal' => false,
            'subscription_required' => false,
            'grace_period_days' => 7,
            'recent_limit' => 5,
            'default_annual_leave' => 21,
            'subscription_vat_percent' => 0,
            'subscription_round_precision' => 2,
        ]);
        
        $this->command->info('Admin settings table seeded with default values.');
    }
}
