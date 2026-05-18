<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Business;
use App\BusinessLocation;
use App\Currency;
use App\InvoiceLayout;
use App\InvoiceScheme;
use App\MpesaPayment;
use App\User;
use App\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SubscriptionPreBillingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Override the in-memory database refresh to use migrate:fresh,
     * ensuring a completely clean schema regardless of prior test state.
     */
    protected function refreshInMemoryDatabase(): void
    {
        $this->artisan('migrate:fresh', $this->migrateUsing());
        $this->app[\Illuminate\Contracts\Console\Kernel::class]->setArtisan(null);
    }

    /** @test */
    public function it_generates_invoice_and_pending_mpesa_for_subscriptions_14_days_before_end()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-09 09:00:00'));

        $user = User::factory()->create();

        $currencyId = DB::table('currencies')->insertGetId([
            'country' => 'Kenya',
            'currency' => 'Kenyan Shilling',
            'code' => 'KES',
            'symbol' => 'KSh',
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $business = Business::create([
            'name' => 'Test Business',
            'currency_id' => $currencyId,
            'start_date' => now()->toDateString(),
            'tax_number_1' => 'A123456789Z',
            'tax_label_1' => 'VAT',
            'owner_id' => $user->id,
            'time_zone' => 'Africa/Nairobi',
            'fy_start_month' => 1,
            'accounting_method' => 'fifo',
            'sell_price_tax' => 'includes',
            'on_product_expiry' => 'keep_selling',
            'stop_selling_before' => 0,
            'weighing_scale_setting' => '{}',
        ]);

        $invoiceLayout = InvoiceLayout::create([
            'name' => 'Default Layout',
            'business_id' => $business->id,
        ]);

        $invoiceScheme = InvoiceScheme::create([
            'business_id' => $business->id,
            'name' => 'Default Scheme',
            'scheme_type' => 'blank',
            'prefix' => 'INV-',
            'start_number' => 1,
            'invoice_count' => 0,
            'total_digits' => 4,
            'is_default' => true,
        ]);

        BusinessLocation::create([
            'business_id' => $business->id,
            'name' => 'Main Branch',
            'country' => 'Kenya',
            'state' => 'Nairobi',
            'city' => 'Nairobi',
            'zip_code' => '00100',
            'invoice_scheme_id' => $invoiceScheme->id,
            'invoice_layout_id' => $invoiceLayout->id,
        ]);

        $user->business_id = $business->id;
        $user->save();

        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_name' => 'Test Plan',
            'billing_cycle' => 'monthly',
            'amount' => 1000,
            'start_date' => today()->subMonth(),
            'end_date' => today()->addDays(14),
            'status' => 'active',
        ]);

        // Run the reminders command
        Artisan::call('subscriptions:send_reminders');

        $sub->refresh();

        $this->assertNotNull($sub->invoice_sent_at, 'invoice_sent_at should be set after running reminders');
        $this->assertNotNull($sub->pending_invoice_transaction_id, 'pending_invoice_transaction_id should be set');
        $this->assertNotNull($sub->pending_mpesa_payment_id, 'pending_mpesa_payment_id should be set');

        // Verify MpesaPayment exists
        $mp = MpesaPayment::find($sub->pending_mpesa_payment_id);
        $this->assertNotNull($mp);

        Carbon::setTestNow();
    }
}
