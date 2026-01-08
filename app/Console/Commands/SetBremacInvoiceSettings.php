<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\AdminSetting;

class SetBremacInvoiceSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:set-bremac';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set BREMAC invoice and subscription related settings in admin_settings';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Setting BREMAC invoice settings...');

        $values = [
            'company_name' => 'BREMAC CONSULTANT LTD',
            // no logo file is provided; keep existing company_logo if present
            'company_contact_phone' => '+1499953505',
            'company_contact_email' => 'admin@bremac.co.ke',
            'invoice_pin' => 'P052182616N',
            'subscription_invoice_prefix' => 'SUB',
            'subscription_invoice_next' => 12,
            'subscription_vat_percent' => 16.00,
            'subscription_round_precision' => 0,
            'invoice_footer' => 'This is a computer-generated invoice and does not require a signature. For any billing inquiries, please contact email [biiling@bremac.co.ke]',
            'statement_footer' => 'Thank you for your business. Please review this statement and notify us within 7 days of any discrepancies. For inquiries, email [accounts@bremac.co.ke]',
        ];

        try {
            $settings = AdminSetting::first();
            if (! $settings) {
                $settings = AdminSetting::create($values);
                $this->info('AdminSetting row created.');
            } else {
                $settings->fill($values);
                $settings->save();
                $this->info('AdminSetting row updated.');
            }

            $this->info('BREMAC invoice settings applied.');
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to apply settings: '.$e->getMessage());
            return 1;
        }
    }
}
