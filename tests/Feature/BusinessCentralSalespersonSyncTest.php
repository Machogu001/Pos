<?php

namespace Tests\Feature;

use App\AdminSetting;
use App\Business;
use App\BusinessCentralSalesperson;
use App\Services\BusinessCentralSyncService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BusinessCentralSalespersonSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_salespeople_without_creating_pos_users(): void
    {
        $business = $this->configureIntegration();

        Http::fake([
            'https://bc.test/BC240/api/v2.0/companies(company-id)/salespeoplePurchasers*' => Http::response([
                'value' => [[
                    'id' => 'salesperson-id',
                    'code' => 'SP001',
                    'displayName' => 'Jane Seller',
                    'email' => 'jane@example.test',
                    'phoneNo' => '0700000000',
                    'blocked' => false,
                ]],
            ]),
        ]);

        $userCount = User::count();
        $count = app(BusinessCentralSyncService::class)->syncSalespeople();

        $this->assertSame(1, $count);
        $this->assertSame($userCount, User::count());
        $this->assertDatabaseHas('business_central_salespeople', [
            'business_id' => $business->id,
            'bc_id' => 'salesperson-id',
            'code' => 'SP001',
            'display_name' => 'Jane Seller',
            'available_in_bc' => true,
            'pos_user_id' => null,
        ]);
        $this->assertFalse(BusinessCentralSalesperson::first()->blocked);
    }

    public function test_it_imports_bc_vendors_as_pos_suppliers(): void
    {
        $business = $this->configureIntegration();

        Http::fake([
            'https://bc.test/BC240/api/v2.0/companies(company-id)/vendors*' => Http::response([
                'value' => [[
                    'id' => 'vendor-id',
                    'number' => 'V0001',
                    'displayName' => 'Medical Supplies Ltd',
                    'addressLine1' => 'Industrial Road',
                    'city' => 'Nairobi',
                    'country' => 'KE',
                    'phoneNumber' => '0711000000',
                    'email' => 'vendor@example.test',
                    'taxRegistrationNumber' => 'P111111111A',
                    'blocked' => false,
                ]],
            ]),
        ]);

        $count = app(BusinessCentralSyncService::class)->syncVendors();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('contacts', [
            'business_id' => $business->id,
            'type' => 'supplier',
            'contact_id' => 'V0001',
            'supplier_business_name' => 'Medical Supplies Ltd',
            'contact_status' => 'active',
        ]);
        $this->assertDatabaseHas('business_central_mappings', [
            'business_id' => $business->id,
            'entity_type' => 'vendor',
            'bc_id' => 'vendor-id',
            'bc_number' => 'V0001',
            'status' => 'synced',
        ]);
    }

    protected function configureIntegration(): Business
    {
        $owner = User::factory()->create();
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
            'name' => 'BC Test Business',
            'currency_id' => $currencyId,
            'tax_number_1' => 'P000000000A',
            'tax_label_1' => 'PIN',
            'owner_id' => $owner->id,
            'stop_selling_before' => 0,
            'weighing_scale_setting' => '{}',
        ]);
        $owner->update(['business_id' => $business->id]);

        AdminSetting::first()->update([
            'business_central_enabled' => true,
            'business_central_business_id' => $business->id,
            'business_central_api_url' => 'https://bc.test/BC240/api/v2.0',
            'business_central_company_id' => 'company-id',
            'business_central_auth_type' => 'basic',
            'business_central_username' => 'pos-user',
            'business_central_secret' => 'secret',
        ]);

        return $business;
    }
}
