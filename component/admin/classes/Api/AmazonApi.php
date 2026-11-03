<?php
/**
 * Amazon Creators API Client
 * Supports v3.x credentials (LwA) and v2.x credentials (Cognito)
 *
 * @package Aaj\Component\Aaj\Administrator\Api
 *
 * Based on official documentation:
 * - Token endpoint v3 EU: https://api.amazon.co.uk/auth/o2/token
 * - Scope v3: creatorsapi::default
 * - API endpoint: https://creatorsapi.amazon
 * - Payload: lowerCamelCase (itemIds, partnerTag, etc.)
 */

namespace Aaj\Component\Aaj\Administrator\Api;

defined('_JEXEC') or die;

use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Log\Log;

class AmazonApi
{
    // ── Credentials ──────────────────────────────────────────────────────────
    private string $clientId;
    private string $clientSecret;
    private string $partnerTag;
    private string $marketplace;   // e.g. www.amazon.fr
    private string $credVersion;   // e.g. 3.2
    private bool   $debug = false;
    private array  $errors = [];

    // Token cache
    private ?string $bearerToken = null;
    private int     $tokenExpiry = 0;

    /**
     * Version → token endpoint map
     */
    private static array $tokenEndpoints = [
        '2.1' => 'https://creatorsapi.auth.us-east-1.amazoncognito.com/oauth2/token',
        '2.2' => 'https://creatorsapi.auth.eu-south-2.amazoncognito.com/oauth2/token',
        '2.3' => 'https://creatorsapi.auth.us-west-2.amazoncognito.com/oauth2/token',
        '3.1' => 'https://api.amazon.com/auth/o2/token',
        '3.2' => 'https://api.amazon.co.uk/auth/o2/token',
        '3.3' => 'https://api.amazon.co.jp/auth/o2/token',
    ];

    /**
     * Marketplace → credential version map
     */
    private static array $marketplaceVersionMap = [
        // EU v3.2
        'www.amazon.fr'     => '3.2',
        'www.amazon.co.uk'  => '3.2',
        'www.amazon.de'     => '3.2',
        'www.amazon.it'     => '3.2',
        'www.amazon.es'     => '3.2',
        'www.amazon.nl'     => '3.2',
        'www.amazon.com.be' => '3.2',
        'www.amazon.in'     => '3.2',
        'www.amazon.ie'     => '3.2',
        'www.amazon.pl'     => '3.2',
        'www.amazon.sa'     => '3.2',
        'www.amazon.se'     => '3.2',
        'www.amazon.com.tr' => '3.2',
        'www.amazon.ae'     => '3.2',
        'www.amazon.eg'     => '3.2',
        // NA v3.1
        'www.amazon.com'    => '3.1',
        'www.amazon.ca'     => '3.1',
        'www.amazon.com.mx' => '3.1',
        'www.amazon.com.br' => '3.1',
        // FE v3.3
        'www.amazon.co.jp'  => '3.3',
        'www.amazon.sg'     => '3.3',
        'www.amazon.com.au' => '3.3',
    ];

    /**
     * Country code → marketplace domain
     */
    private static array $countryToMarketplace = [
        'fr'     => 'www.amazon.fr',
        'co.uk'  => 'www.amazon.co.uk',
        'de'     => 'www.amazon.de',
        'it'     => 'www.amazon.it',
        'es'     => 'www.amazon.es',
        'nl'     => 'www.amazon.nl',
        'com.be' => 'www.amazon.com.be',
        'in'     => 'www.amazon.in',
        'ie'     => 'www.amazon.ie',
        'pl'     => 'www.amazon.pl',
        'sa'     => 'www.amazon.sa',
        'se'     => 'www.amazon.se',
        'com.tr' => 'www.amazon.com.tr',
        'ae'     => 'www.amazon.ae',
        'eg'     => 'www.amazon.eg',
        'com'    => 'www.amazon.com',
        'ca'     => 'www.amazon.ca',
        'com.mx' => 'www.amazon.com.mx',
        'com.br' => 'www.amazon.com.br',
        'co.jp'  => 'www.amazon.co.jp',
        'sg'     => 'www.amazon.sg',
        'com.au' => 'www.amazon.com.au',
    ];

    /**
     * Default resources for GetItems
     * Valid values come from the API enum — do NOT add anything not in this list.
     */
    private static array $defaultResources = [
        'images.primary.small',
        'images.primary.medium',
        'images.primary.large',
        'itemInfo.title',
        'itemInfo.features',
        'itemInfo.byLineInfo',
        'itemInfo.productInfo',
        'itemInfo.externalIds',
        'itemInfo.technicalInfo',
        'offersV2.listings.price',
        'offersV2.listings.availability',
        'offersV2.listings.condition',
        'offersV2.listings.dealDetails',
        'offersV2.listings.isBuyBoxWinner',
        'browseNodeInfo.browseNodes',
        'browseNodeInfo.browseNodes.salesRank',
        'customerReviews.starRating',
        'customerReviews.count',
        'parentASIN',
    ];

    // ── Constructor ───────────────────────────────────────────────────────────

    public function __construct(string $clientId, string $clientSecret, string $partnerTag, string $country = 'fr')
    {
        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;
        $this->partnerTag   = $partnerTag;
        $this->setCountry($country);
    }

    /**
     * Factory method — same signature as before for compatibility
     */
    public static function create(string $key, string $secret, string $partnerTag, string $country = 'fr'): self
    {
        return new self($key, $secret, $partnerTag, $country);
    }

    public function setCountry(string $country): void
    {
        $this->marketplace  = self::$countryToMarketplace[$country] ?? 'www.amazon.fr';
        $this->credVersion  = self::$marketplaceVersionMap[$this->marketplace] ?? '3.2';
    }

    public function setDebug(bool $debug = true): void { $this->debug = $debug; }
    public function getErrors(): array { return $this->errors; }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Get items by ASIN(s) — max 10
     */
    public function getItems($asins, array $resources = []): ?array
    {
        if (is_string($asins)) $asins = [$asins];

        $payload = [
            'itemIds'     => array_values(array_slice($asins, 0, 10)),
            'itemIdType'  => 'ASIN',
            'marketplace' => $this->marketplace,
            'partnerTag'  => $this->partnerTag,
            'partnerType' => 'Associates',
            'resources'   => $resources ?: self::$defaultResources,
        ];

        $resp = $this->request('/catalog/v1/getItems', $payload);
        if (!$resp) return null;

        if (isset($resp['errors'])) {
            $this->handleErrors($resp['errors']);
            return null;
        }

        return $resp['itemsResult']['items'] ?? null;
    }

    /**
     * Search items by keywords
     */
    public function searchItems(string $keywords, string $searchIndex = 'All', int $count = 10): ?array
    {
        $payload = [
            'keywords'    => $keywords,
            'searchIndex' => $searchIndex,
            'itemCount'   => min($count, 10),
            'marketplace' => $this->marketplace,
            'partnerTag'  => $this->partnerTag,
            'partnerType' => 'Associates',
            'resources'   => self::$defaultResources,
        ];

        $resp = $this->request('/catalog/v1/searchItems', $payload);
        if (!$resp) return null;
        if (isset($resp['errors'])) { $this->handleErrors($resp['errors']); return null; }
        return $resp['searchResult']['items'] ?? null;
    }

    /**
     * Get browse nodes (for bestsellers/new releases)
     */
    public function getListItems(string $browseNodeId, string $type = 'BestSellers', int $count = 10): ?array
    {
        $payload = [
            'browseNodeId' => $browseNodeId,
            'marketplace'  => $this->marketplace,
            'partnerTag'   => $this->partnerTag,
            'partnerType'  => 'Associates',
            'resources'    => ['browseNodes.topSellers', 'browseNodes.newReleases'],
        ];

        $resp = $this->request('/catalog/v1/getBrowseNodes', $payload);
        if (!$resp) return null;
        if (isset($resp['errors'])) { $this->handleErrors($resp['errors']); return null; }

        $nodes = $resp['browseNodesResult']['browseNodes'] ?? [];
        $asins = [];
        foreach ($nodes as $node) {
            $items = $type === 'NewReleases' ? ($node['newReleases'] ?? []) : ($node['topSellers'] ?? []);
            foreach ($items as $item) {
                if (!empty($item['asin'])) $asins[] = $item['asin'];
                if (count($asins) >= $count) break 2;
            }
        }

        return $asins ? $this->getItems(array_unique($asins)) : null;
    }

    /**
     * Test credentials
     */
    public function testCredentials(): bool
    {
        $token = $this->getBearerToken();
        return $token !== null;
    }

    // ── Authentication ────────────────────────────────────────────────────────

    private function getBearerToken(): ?string
    {
        if ($this->bearerToken && time() < $this->tokenExpiry - 60) {
            return $this->bearerToken;
        }

        $endpoint = self::$tokenEndpoints[$this->credVersion] ?? self::$tokenEndpoints['3.2'];
        $isLwa    = str_starts_with($this->credVersion, '3');

        try {
            $http = HttpFactory::getHttp();

            if ($isLwa) {
                // v3.x : JSON body with scope
                $body    = json_encode([
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope'         => 'creatorsapi::default',
                ]);
                $headers = ['Content-Type' => 'application/json'];
            } else {
                // v2.x : form-encoded with Basic auth
                $body    = 'grant_type=client_credentials&scope=creatorsapi/default';
                $creds   = base64_encode($this->clientId . ':' . $this->clientSecret);
                $headers = [
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . $creds,
                ];
            }

            $resp = $http->post($endpoint, $body, $headers, 30);

            if ($this->debug) {
                Log::add("AAJ Token request to {$endpoint} → HTTP {$resp->code}: {$resp->body}", Log::DEBUG, 'com_aaj');
            }

            if ($resp->code !== 200) {
                $this->errors[] = ['Code' => $resp->code, 'Message' => 'Token request failed: ' . $resp->body];
                return null;
            }

            $data = json_decode($resp->body, true);
            if (empty($data['access_token'])) {
                $this->errors[] = ['Code' => 'TokenError', 'Message' => $resp->body];
                return null;
            }

            $this->bearerToken = $data['access_token'];
            $this->tokenExpiry = time() + (int)($data['expires_in'] ?? 3600);
            return $this->bearerToken;

        } catch (\Exception $e) {
            $this->errors[] = ['Code' => 0, 'Message' => 'Token exception: ' . $e->getMessage()];
            return null;
        }
    }

    // ── HTTP Request ──────────────────────────────────────────────────────────

    private function request(string $path, array $payload): ?array
    {
        $token = $this->getBearerToken();
        if (!$token) return null;

        // v3: Authorization: Bearer <token>
        // v2: Authorization: Bearer <token>, Version <version>
        $authHeader = str_starts_with($this->credVersion, '3')
            ? "Bearer {$token}"
            : "Bearer {$token}, Version {$this->credVersion}";

        $headers = [
            'Authorization' => $authHeader,
            'Content-Type'  => 'application/json',
            'x-marketplace' => $this->marketplace,
        ];

        $url  = 'https://creatorsapi.amazon' . $path;
        $body = json_encode($payload);

        if ($this->debug) {
            Log::add("AAJ API Request: {$url}", Log::DEBUG, 'com_aaj');
            Log::add("AAJ API Payload: {$body}", Log::DEBUG, 'com_aaj');
        }

        try {
            $http = HttpFactory::getHttp();
            $resp = $http->post($url, $body, $headers, 30);

            if ($this->debug) {
                Log::add("AAJ API Response [{$resp->code}]: {$resp->body}", Log::DEBUG, 'com_aaj');
            }

            $data = json_decode($resp->body, true);

            if ($resp->code === 200) return $data;

            $this->errors[] = [
                'Code'    => $resp->code,
                'Message' => ($data['message'] ?? $data['errors'][0]['message'] ?? $resp->body),
            ];
            return null;

        } catch (\Exception $e) {
            $this->errors[] = ['Code' => 0, 'Message' => $e->getMessage()];
            return null;
        }
    }

    private function handleErrors(array $errors): void
    {
        foreach ($errors as $error) {
            $this->errors[] = [
                'Code'    => $error['code'] ?? $error['Code'] ?? 'Unknown',
                'Message' => $error['message'] ?? $error['Message'] ?? 'Unknown error',
            ];
        }
    }
}
