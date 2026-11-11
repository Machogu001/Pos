<?php

namespace Tests\Unit;

use App\AdminSetting;
use App\Services\BusinessCentralClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BusinessCentralClientTest extends TestCase
{
    public function test_it_uses_the_configured_company_and_basic_authentication(): void
    {
        Http::fake([
            'https://bc.test/BC240/api/v2.0/companies(company-id)/items*' => Http::response([
                'value' => [['id' => 'item-id', 'number' => '1000']],
            ]),
        ]);

        $client = new BusinessCentralClient($this->settings());
        $items = $client->getCollection('items', ['$select' => 'id,number']);

        $this->assertSame('1000', $items[0]['number']);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://bc.test/BC240/api/v2.0/companies(company-id)/items?%24select=id%2Cnumber'
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('pos-user:secret'));
        });
    }

    public function test_inventory_request_filters_the_configured_bc_location(): void
    {
        Http::fake([
            'https://bc.test/custom/companies(company-id)/posInventoryEntries*' => Http::response([
                'value' => [],
            ]),
        ]);

        $settings = $this->settings();
        $settings->business_central_inventory_url = 'https://bc.test/custom/companies({company_id})/posInventoryEntries';
        $settings->business_central_location_code = 'MAIN';

        (new BusinessCentralClient($settings))->inventory();

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://bc.test/custom/companies(company-id)/posInventoryEntries?')
                && str_contains(urldecode($request->url()), "\$filter=locationCode eq 'MAIN' and remainingQuantity ne 0");
        });
    }

    protected function settings(): AdminSetting
    {
        $settings = new AdminSetting();
        $settings->business_central_api_url = 'https://bc.test/BC240/api/v2.0';
        $settings->business_central_company_id = 'company-id';
        $settings->business_central_auth_type = 'basic';
        $settings->business_central_username = 'pos-user';
        $settings->business_central_secret = 'secret';

        return $settings;
    }
}
