<?php

namespace App\Services;

use App\AdminSetting;
use App\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EtimsService
{
    /**
     * Transmit invoice to eTIMS API
     *
     * @param Transaction $transaction
     * @return array
     */
    public function transmitInvoice(Transaction $transaction)
    {
        try {
            // Get eTIMS settings
            $settings = AdminSetting::first();

            // Check if eTIMS is configured
            if (empty($settings->etims_api_url) || empty($settings->etims_api_token)) {
                Log::warning('eTIMS API not configured', ['transaction_id' => $transaction->id]);
                return [
                    'success' => false,
                    'message' => 'eTIMS API not configured'
                ];
            }

            // Build invoice payload
            $payload = $this->buildInvoicePayload($transaction, $settings);

            // Log the payload for debugging
            Log::info('eTIMS Invoice Payload', [
                'transaction_id' => $transaction->id,
                'invoice_no' => $transaction->invoice_no,
                'payload' => $payload
            ]);

            // Send to eTIMS API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $settings->etims_api_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->timeout(30)->post($settings->etims_api_url, $payload);

            // Log response
            Log::info('eTIMS API Response', [
                'transaction_id' => $transaction->id,
                'status_code' => $response->status(),
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                // Update transaction with successful transmission
                $transaction->update([
                    'etims_transmitted' => true,
                    'etims_transmitted_at' => now(),
                    'etims_response' => json_encode($response->json()),
                    'etims_error' => null
                ]);

                return [
                    'success' => true,
                    'message' => 'Invoice transmitted successfully to eTIMS',
                    'response' => $response->json()
                ];
            } else {
                // Update transaction with failed transmission
                $transaction->update([
                    'etims_transmitted' => false,
                    'etims_error' => $response->body()
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to transmit invoice to eTIMS',
                    'error' => $response->body(),
                    'status_code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            // Update transaction with exception error
            $transaction->update([
                'etims_transmitted' => false,
                'etims_error' => $e->getMessage()
            ]);

            Log::error('eTIMS transmission error', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Exception during eTIMS transmission: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Build invoice payload for eTIMS API
     *
     * @param Transaction $transaction
     * @param AdminSetting $settings
     * @return array
     */
    protected function buildInvoicePayload(Transaction $transaction, AdminSetting $settings)
    {
        // Load relationships
        $transaction->load(['contact', 'sell_lines.product.unit', 'business.currency']);

        // Prepare customer information
        $customerName = $transaction->contact ? $transaction->contact->name : 'Walk-in Customer';
        $customerPIN = $transaction->contact && $transaction->contact->tax_number 
            ? $transaction->contact->tax_number 
            : '';

        // Prepare item list
        $itemList = [];
        foreach ($transaction->sell_lines as $line) {
            $product = $line->product;
            
            // Calculate unit price excluding tax
            $unitPriceExcl = $line->unit_price_before_discount;
            $taxRate = $line->item_tax ?? 0;
            
            // If tax is included in the price, calculate exclusive price
            if ($transaction->tax_calculation_method === 'inclusive') {
                $unitPriceExcl = $unitPriceExcl / (1 + ($taxRate / 100));
            }

            $itemList[] = [
                'ItemCode' => $product->sku ?? 'ITEM-' . $line->id,
                'ItemClassCode' => $product->category_id ?? '4323151200', // Default class code
                'ItemName' => $product->name ?? 'Product',
                'PackagingUnitCode' => 'OU', // Default packaging unit
                'QuantityUnitCode' => $product->unit->short_name ?? 'U',
                'Quantity' => (float) $line->quantity,
                'UnitPriceExcl' => round($unitPriceExcl, 2),
                'TaxRate' => (float) $taxRate,
                'TaxationTypeCode' => $this->getTaxationTypeCode($taxRate),
                'DiscountRate' => $line->line_discount_type === 'percentage' ? (float) $line->line_discount_amount : 0,
                'DiscountAmount' => $line->line_discount_type === 'fixed' ? (float) $line->line_discount_amount : 0,
            ];
        }

        // Get TIN from business settings (Tax 1 No.)
        $businessTIN = $transaction->business->tax_number_1 ?? $settings->invoice_pin ?? '';

        $currencyCode = strtoupper((string) ($transaction->business->currency->code ?? 'KES'));
        if (strlen($currencyCode) !== 3) {
            $currencyCode = 'KES';
        }

        $exchangeRate = (float) ($transaction->exchange_rate ?? 1);
        if ($exchangeRate <= 0) {
            $exchangeRate = 1;
        }

        // Build main payload
        return [
            'tin' => $businessTIN,
            'BranchId' => $settings->etims_branch_id ?? '02',
            'DocumentType' => 'Sale',
            'InvoiceNo' => $transaction->invoice_no,
            'CustPIN' => $customerPIN,
            'CustName' => $customerName,
            'SaleDate' => $transaction->transaction_date->format('Y-m-d'),
            'CurrencyCode' => $currencyCode,
            'ExchangeRate' => $exchangeRate,
            'RefInvoiceNo' => 0,
            'CreditNoteReason' => '',
            'CreatedBy' => 'SYSTEM',
            'CreatedByName' => $settings->company_name ?? $transaction->business->name ?? 'POS System',
            'itemList' => $itemList
        ];
    }

    /**
     * Get taxation type code based on tax rate
     *
     * @param float $taxRate
     * @return string
     */
    protected function getTaxationTypeCode($taxRate)
    {
        // KRA eTIMS taxation type codes
        // A = Exempt (0%)
        // B = VAT Standard Rate (16%)
        // C = Zero Rated
        // E = Special Rate
        
        if ($taxRate == 0) {
            return 'A'; // Exempt
        } elseif ($taxRate == 16) {
            return 'B'; // VAT 16%
        } else {
            return 'E'; // Special rate
        }
    }

    /**
     * Check if auto-transmit is enabled
     *
     * @return bool
     */
    public static function isAutoTransmitEnabled()
    {
        $settings = AdminSetting::first();
        return $settings && $settings->etims_auto_transmit;
    }

    /**
     * Check if transaction should be transmitted to eTIMS
     *
     * @param Transaction $transaction
     * @return bool
     */
    public static function shouldTransmit(Transaction $transaction)
    {
        $settings = AdminSetting::first();
        
        // Auto-transmit must be enabled
        if (!$settings || !$settings->etims_auto_transmit) {
            return false;
        }

        // Check transaction type - only transmit 'sell' type
        if ($transaction->type !== 'sell') {
            return false;
        }

        // Check if it's a subscription invoice (has sub_type or related to subscription)
        if (isset($transaction->sub_type) && $transaction->sub_type === 'subscription') {
            return $settings->etims_transmit_subscriptions ?? false;
        }

        // Check if it's a registration payment
        if (isset($transaction->sub_type) && $transaction->sub_type === 'registration') {
            return $settings->etims_transmit_registrations ?? false;
        }

        // Default: transmit all other product sales
        return true;
    }
}
