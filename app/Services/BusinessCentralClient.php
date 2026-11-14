<?php

namespace App\Services;

use App\AdminSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BusinessCentralClient
{
    public function __construct(protected AdminSetting $settings)
    {
    }

    public function getCollection(string $resource, array $query = [], ?string $absoluteUrl = null): array
    {
        $rows = [];
        $url = $absoluteUrl ?: $this->resourceUrl($resource);

        do {
            $response = $this->request()->retry(2, 300, null, false)->get($url, $query);
            $this->ensureSuccessful($response, 'read '.$resource);
            $data = $response->json();
            if (! is_array($data) || ! array_key_exists('value', $data)) {
                throw new RuntimeException('Business Central returned an invalid JSON collection while reading '.$resource.'.');
            }
            $rows = array_merge($rows, $data['value'] ?? []);
            $url = $data['@odata.nextLink'] ?? null;
            $query = [];
        } while ($url);

        return $rows;
    }

    public function companies(): array
    {
        $url = rtrim((string) $this->settings->business_central_api_url, '/').'/companies';

        return $this->getCollection('companies', [
            '$select' => 'id,name,displayName',
        ], $url);
    }

    public function salesInvoicePdf(string $invoiceId): string
    {
        $invoiceId = trim($invoiceId, "{} \t\n\r\0\x0B");
        $resource = 'salesInvoices('.rawurlencode($invoiceId).')/pdfDocument';
        $response = $this->request()->get($this->resourceUrl($resource));
        $this->ensureSuccessful($response, 'read sales invoice PDF');

        if (str_starts_with((string) $response->header('Content-Type'), 'application/pdf')) {
            return $response->body();
        }

        $data = $response->json();
        $document = is_array($data['value'] ?? null)
            ? ($data['value'][0] ?? [])
            : $data;
        $contentUrl = $document['pdfDocumentContent@odata.mediaReadLink']
            ?? $document['content@odata.mediaReadLink']
            ?? null;
        if (empty($contentUrl)) {
            throw new RuntimeException('Business Central returned no PDF content link for this sales invoice.');
        }

        $contentResponse = $this->request()->get($contentUrl);
        $this->ensureSuccessful($contentResponse, 'download sales invoice PDF');
        $pdf = $contentResponse->body();
        if ($pdf === '') {
            throw new RuntimeException('Business Central returned an empty sales invoice PDF.');
        }

        return $pdf;
    }

    public function post(string $resource, array $payload): array
    {
        $response = $this->request()->post($this->resourceUrl($resource), $payload);
        $this->ensureSuccessful($response, 'create '.$resource);

        return $response->json() ?: [];
    }

    public function postAbsolute(string $url, array $payload = []): array
    {
        $response = $this->request()->post($url, $payload);
        $this->ensureSuccessful($response, 'post Business Central document');

        return $response->json() ?: [];
    }

    public function delete(string $resource): bool
    {
        $response = $this->request()->delete($this->resourceUrl($resource));
        if ($response->successful()) {
            return true;
        }
        if ($response->status() === 404) {
            return false;
        }

        $this->ensureSuccessful($response, 'delete '.$resource);

        return false;
    }

    public function inventory(?string $locationCode = null): array
    {
        $url = str_replace(
            '{company_id}',
            rawurlencode((string) $this->settings->business_central_company_id),
            (string) $this->settings->business_central_inventory_url
        );

        if ($url === '') {
            return $this->getCollection('items', [
                '$select' => 'id,number,inventory,lastModifiedDateTime',
            ]);
        }

        $query = ['$select' => 'entryNo,itemNumber,locationCode,remainingQuantity,lastModifiedDateTime'];
        $locationCode = trim((string) ($locationCode ?: $this->settings->business_central_location_code));
        if ($locationCode !== '') {
            $query['$filter'] = "locationCode eq '".str_replace("'", "''", $locationCode)."' and remainingQuantity ne 0";
        }

        return $this->getCollection('posInventoryEntries', $query, $url);
    }

    public function itemUnits(): array
    {
        $inventoryUrl = (string) $this->settings->business_central_inventory_url;
        $url = preg_replace(
            '~/posInventoryEntries/?(?:\?.*)?$~i',
            '/posItemUnits',
            $inventoryUrl
        );
        if (empty($url) || $url === $inventoryUrl) {
            $apiUrl = rtrim((string) $this->settings->business_central_api_url, '/');
            $customApiBase = preg_replace(
                '~/api/v2\.0$~i',
                '/api/bremac/pos/v1.0',
                $apiUrl
            );
            if (empty($customApiBase) || $customApiBase === $apiUrl) {
                throw new RuntimeException(
                    'The Business Central API URL must end with api/v2.0 to resolve posItemUnits.'
                );
            }
            $url = $customApiBase.'/companies('
                .rawurlencode((string) $this->settings->business_central_company_id)
                .')/posItemUnits';
        } else {
            $url = str_replace(
                '{company_id}',
                rawurlencode((string) $this->settings->business_central_company_id),
                $url
            );
        }
        if (empty($url)) {
            throw new RuntimeException(
                'The Business Central posItemUnits URL could not be resolved.'
            );
        }

        return $this->getCollection('posItemUnits', [
            '$select' => 'id,itemNumber,code,qtyPerUnitOfMeasure,lastModifiedDateTime',
        ], $url);
    }

    public function salespeople(): array
    {
        $url = trim((string) $this->settings->business_central_salespeople_url);
        if ($url !== '') {
            return $this->getCollection('salespeople', [], $url);
        }

        return $this->getCollection('salespeoplePurchasers', [
            '$select' => 'id,code,displayName,email,phoneNo,blocked',
        ]);
    }

    public function hasSalesCodeunit(): bool
    {
        return trim((string) $this->settings->business_central_sales_codeunit_url) !== '';
    }

    public function postSale(array $payload): array
    {
        $url = trim((string) $this->settings->business_central_sales_codeunit_url);
        if ($url === '') {
            throw new RuntimeException('The Business Central POS sales codeunit URL is not configured.');
        }

        $serviceName = basename((string) parse_url($url, PHP_URL_PATH));
        if ($serviceName === '') {
            throw new RuntimeException('The Business Central POS sales codeunit URL has no service name.');
        }
        $namespace = 'urn:microsoft-dynamics-schemas/codeunit/'.$serviceName;
        $document = new \DOMDocument('1.0', 'UTF-8');
        $envelope = $document->createElementNS('http://schemas.xmlsoap.org/soap/envelope/', 'soap:Envelope');
        $body = $document->createElementNS('http://schemas.xmlsoap.org/soap/envelope/', 'soap:Body');
        $operation = $document->createElementNS($namespace, 'PostSaleV4');
        $saleJson = $document->createElement('saleJson');
        $saleJson->appendChild($document->createTextNode(json_encode($payload, JSON_THROW_ON_ERROR)));
        $operation->appendChild($saleJson);
        $body->appendChild($operation);
        $envelope->appendChild($body);
        $document->appendChild($envelope);

        $response = $this->request()
            ->withHeaders(['SOAPAction' => $namespace.':PostSaleV4'])
            ->withBody($document->saveXML(), 'text/xml; charset=utf-8')
            ->post($url);

        return $this->parseSoapResponse($response, 'post POS sale');
    }

    public function resourceUrl(string $resource): string
    {
        $base = rtrim((string) $this->settings->business_central_api_url, '/');
        $companyId = rawurlencode((string) $this->settings->business_central_company_id);

        return $base.'/companies('.$companyId.')/'.ltrim($resource, '/');
    }

    protected function request(): PendingRequest
    {
        $request = Http::acceptJson()->asJson()->timeout(30);
        $authType = $this->settings->business_central_auth_type ?: 'basic';

        if ($authType === 'basic') {
            return $request->withBasicAuth(
                (string) $this->settings->business_central_username,
                (string) $this->settings->business_central_secret
            );
        }

        return $request->withToken($authType === 'oauth2'
            ? $this->oauthToken()
            : (string) $this->settings->business_central_secret);
    }

    protected function oauthToken(): string
    {
        $cacheKey = 'business-central-token-'.sha1((string) $this->settings->business_central_client_id);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            $response = Http::asForm()->timeout(20)->post(
                (string) $this->settings->business_central_token_url,
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->settings->business_central_client_id,
                    'client_secret' => $this->settings->business_central_secret,
                    'scope' => $this->settings->business_central_scope,
                ]
            );
            $this->ensureSuccessful($response, 'authenticate');

            $token = $response->json('access_token');
            if (empty($token)) {
                throw new RuntimeException('Business Central authentication returned no access token.');
            }

            return $token;
        });
    }

    protected function ensureSuccessful($response, string $operation): void
    {
        if ($response->successful()) {
            return;
        }

        $message = $response->json('error.message') ?: $response->body();
        throw new RuntimeException(
            'Business Central could not '.$operation.' (HTTP '.$response->status().'): '.mb_substr($message, 0, 1000)
        );
    }

    protected function parseSoapResponse($response, string $operation): array
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML((string) $response->body(), LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new RuntimeException('Business Central returned invalid SOAP XML while attempting to '.$operation.'.');
        }

        $xpath = new \DOMXPath($document);
        $fault = $xpath->query('//*[local-name()="faultstring"]')->item(0);
        if (! $response->successful() || $fault) {
            $message = $fault?->textContent ?: 'HTTP '.$response->status();
            throw new RuntimeException('Business Central could not '.$operation.': '.mb_substr($message, 0, 1000));
        }

        $returnValue = $xpath->query('//*[local-name()="return_value"]')->item(0);
        if (! $returnValue) {
            throw new RuntimeException('Business Central returned no result while attempting to '.$operation.'.');
        }

        $result = json_decode($returnValue->textContent, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($result) || empty($result['success'])) {
            throw new RuntimeException('Business Central returned an unsuccessful result while attempting to '.$operation.'.');
        }

        return $result;
    }
}
