<?php

namespace Tests\Unit;

use App\AdminSetting;
use App\Services\BusinessCentralClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BusinessCentralClientTest extends TestCase
{
    public function test_it_downloads_a_sales_invoice_pdf_from_the_bc_media_link(): void
    {
        Http::fake([
            'https://bc.test/BC240/api/v2.0/companies(company-id)/salesInvoices(invoice-id)/pdfDocument' => Http::response([
                'value' => [[
                    'id' => 'invoice-id',
                    'pdfDocumentContent@odata.mediaReadLink' => 'https://bc.test/media/invoice-id',
                ]],
            ]),
            'https://bc.test/media/invoice-id' => Http::response(
                '%PDF-1.4 test invoice',
                200,
                ['Content-Type' => 'application/pdf']
            ),
        ]);

        $pdf = (new BusinessCentralClient($this->settings()))->salesInvoicePdf('{invoice-id}');

        $this->assertStringStartsWith('%PDF-', $pdf);
        Http::assertSent(fn ($request) => $request->url() === 'https://bc.test/media/invoice-id');
    }

    public function test_it_loads_companies_without_requiring_a_company_id(): void
    {
        Http::fake([
            'https://bc.test/BC240/api/v2.0/companies*' => Http::response([
                'value' => [[
                    'id' => 'company-id',
                    'name' => 'CRONUS',
                    'displayName' => 'CRONUS Kenya Ltd',
                ]],
            ]),
        ]);

        $settings = $this->settings();
        $settings->business_central_company_id = null;
        $companies = (new BusinessCentralClient($settings))->companies();

        $this->assertSame('company-id', $companies[0]['id']);
        $this->assertSame('CRONUS Kenya Ltd', $companies[0]['displayName']);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://bc.test/BC240/api/v2.0/companies?%24select=id%2Cname%2CdisplayName'
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('pos-user:secret'));
        });
    }

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

    public function test_salespeople_uses_the_configured_odata_collection(): void
    {
        Http::fake([
            "https://bc.test/BC240/ODataV4/Company('TEST')/SalespeoplePurchasers" => Http::response([
                'value' => [['Code' => 'SP001', 'Name' => 'Jane Seller']],
            ]),
        ]);

        $settings = $this->settings();
        $settings->business_central_salespeople_url = "https://bc.test/BC240/ODataV4/Company('TEST')/SalespeoplePurchasers";

        $rows = (new BusinessCentralClient($settings))->salespeople();

        $this->assertSame('SP001', $rows[0]['Code']);
        Http::assertSent(fn ($request) => $request->url() === $settings->business_central_salespeople_url);
    }

    public function test_it_posts_a_sale_through_the_configured_soap_codeunit(): void
    {
        Http::fake([
            'https://bc.test/BC240/WS/TEST/Codeunit/BreMacPOSSalesIntegration' => Http::response(
                '<?xml version="1.0" encoding="utf-8"?>'
                .'<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
                .'<soap:Body><PostSaleV4_Result xmlns="urn:microsoft-dynamics-schemas/codeunit/BreMacPOSSalesIntegration">'
                .'<return_value>{"success":true,"id":"posted-id","number":"INV-1"}</return_value>'
                .'</PostSaleV4_Result></soap:Body></soap:Envelope>',
                200,
                ['Content-Type' => 'text/xml']
            ),
        ]);

        $settings = $this->settings();
        $settings->business_central_sales_codeunit_url = 'https://bc.test/BC240/WS/TEST/Codeunit/BreMacPOSSalesIntegration';
        $client = new BusinessCentralClient($settings);

        $result = $client->postSale([
            'externalDocumentNumber' => 'SNV0003',
            'customerNumber' => 'CUS003018',
            'postingDate' => '2026-11-13',
            'salespersonCode' => 'SHERRY',
            'locationCode' => 'HQ',
            'lines' => [[
                'lineObjectNumber' => 'ITM00038',
                'unitOfMeasureCode' => 'PCS',
                'quantity' => 1,
                'unitPrice' => 6.05,
            ]],
        ]);

        $this->assertSame('INV-1', $result['number']);
        Http::assertSent(function ($request) {
            return $request->hasHeader(
                'SOAPAction',
                'urn:microsoft-dynamics-schemas/codeunit/BreMacPOSSalesIntegration:PostSaleV4'
            )
                && str_contains($request->body(), '<saleJson>')
                && str_contains($request->body(), '"locationCode":"HQ"')
                && str_contains($request->body(), '"unitOfMeasureCode":"PCS"')
                && str_contains($request->body(), '"unitPrice":6.05');
        });
    }

    public function test_it_resolves_item_units_from_the_custom_api_when_inventory_uses_odata(): void
    {
        Http::fake([
            'https://bc.test/BC240/api/bremac/pos/v1.0/companies(company-id)/posItemUnits*' => Http::response([
                'value' => [[
                    'itemNumber' => 'ITM00038',
                    'code' => 'WHL',
                    'qtyPerUnitOfMeasure' => 100,
                ]],
            ]),
        ]);

        $settings = $this->settings();
        $settings->business_central_inventory_url =
            "https://bc.test/BC240/ODataV4/Company('TEST')/BreMacPOSInventoryEntries";

        $rows = (new BusinessCentralClient($settings))->itemUnits();

        $this->assertSame('WHL', $rows[0]['code']);
        Http::assertSent(fn ($request) => str_starts_with(
            $request->url(),
            'https://bc.test/BC240/api/bremac/pos/v1.0/companies(company-id)/posItemUnits'
        ));
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
