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
            $response = $this->request()->retry(2, 300)->get($url, $query);
            $this->ensureSuccessful($response, 'read '.$resource);
            $data = $response->json();
            $rows = array_merge($rows, $data['value'] ?? []);
            $url = $data['@odata.nextLink'] ?? null;
            $query = [];
        } while ($url);

        return $rows;
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
}
