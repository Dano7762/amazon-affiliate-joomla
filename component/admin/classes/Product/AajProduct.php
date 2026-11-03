<?php
/**
 * AAJ Product class — wraps a single product row from DB or API response
 *
 * @package Aaj\Component\Aaj
 */

namespace Aaj\Component\Aaj\Administrator\Product;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

/**
 * Represents a single Amazon product
 */
class AajProduct
{
    /** @var array Raw product data */
    private array $data;

    /** @var string Current Amazon store (e.g. 'fr', 'de', 'com') */
    private string $store;

    /** @var string Placeholder for tracking ID replacement at output time */
    public const PLACEHOLDER_TAG = '{AAJ_TAG}';

    /**
     * Constructor
     */
    public function __construct(array $data, string $store = 'com')
    {
        $this->data  = $data;
        $this->store = $store;
    }

    /* -------------------------------------------------------------------------
     * Identifiers
     * ---------------------------------------------------------------------- */

    public function getId(): int
    {
        return !empty($this->data['id']) ? (int) $this->data['id'] : 0;
    }

    public function getType(): string
    {
        return $this->data['type'] ?? 'product';
    }

    public function getStatus(): string
    {
        return $this->data['status'] ?? 'active';
    }

    public function getAsin(): string
    {
        return $this->data['asin'] ?? '';
    }

    public function getEan(): string
    {
        return $this->data['ean'] ?? '';
    }

    public function getIsbn(): string
    {
        return $this->data['isbn'] ?? '';
    }

    public function getBinding(): string
    {
        return $this->data['binding'] ?? '';
    }

    public function getProductGroup(): string
    {
        return $this->data['product_group'] ?? '';
    }

    /* -------------------------------------------------------------------------
     * Content
     * ---------------------------------------------------------------------- */

    public function getTitle(int $limit = 0): string
    {
        $title = trim($this->data['title'] ?? '');
        if ($limit > 0 && mb_strlen($title) > $limit) {
            $title = mb_substr($title, 0, $limit) . '…';
        }
        return htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    }

    public function getRawTitle(): string
    {
        return trim($this->data['title'] ?? '');
    }

    /**
     * Get product features as an array
     */
    public function getFeatures(): array
    {
        if (empty($this->data['features'])) {
            return [];
        }
        $decoded = json_decode($this->data['features'], true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get product description (first N features joined)
     */
    public function getDescription(int $items = 5, int $charLimit = 0): string
    {
        $features = array_slice($this->getFeatures(), 0, $items);
        if (empty($features)) {
            return '';
        }
        $desc = implode(' ', $features);
        if ($charLimit > 0 && mb_strlen($desc) > $charLimit) {
            $desc = mb_substr($desc, 0, $charLimit) . '…';
        }
        return $desc;
    }

    /**
     * Get attributes as array
     */
    public function getAttributes(): array
    {
        if (empty($this->data['attributes'])) {
            return [];
        }
        $decoded = json_decode($this->data['attributes'], true);
        return is_array($decoded) ? $decoded : [];
    }

    /* -------------------------------------------------------------------------
     * URLs
     * ---------------------------------------------------------------------- */

    /**
     * Get product URL (detail / cart / reviews)
     */
    public function getUrl(string $type = 'basic'): string
    {
        $asin = $this->getAsin();
        $tag  = self::PLACEHOLDER_TAG;
        $tld  = $this->store;

        switch ($type) {
            case 'cart':
                return "https://www.amazon.{$tld}/gp/aws/cart/add.html?ASIN.1={$asin}&Quantity.1=1&AssociateTag={$tag}";
            case 'reviews':
                return "https://www.amazon.{$tld}/product-reviews/{$asin}?tag={$tag}";
            default:
                $url = $this->data['url'] ?? '';
                return $url ?: "https://www.amazon.{$tld}/dp/{$asin}?tag={$tag}";
        }
    }

    /**
     * Replace placeholder tag with actual tracking ID
     */
    public function buildUrl(string $type, string $trackingId): string
    {
        return str_replace(self::PLACEHOLDER_TAG, $trackingId, $this->getUrl($type));
    }

    /* -------------------------------------------------------------------------
     * Images
     * ---------------------------------------------------------------------- */

    /**
     * Get image IDs array
     */
    private function getImageIds(): array
    {
        if (empty($this->data['image_ids'])) {
            return [];
        }
        $decoded = json_decode($this->data['image_ids'], true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get image URL for a given size
     *
     * @param string $size  small | medium | large
     */
    public function getImage(string $size = 'medium'): string
    {
        $ids = $this->getImageIds();
        $key = match ($size) {
            'small'  => 'small',
            'large'  => 'large',
            default  => 'medium',
        };
        return $ids[$key] ?? '';
    }

    /* -------------------------------------------------------------------------
     * Pricing
     * ---------------------------------------------------------------------- */

    public function getAvailability(): string
    {
        return $this->data['availability'] ?? '';
    }

    public function isAvailable(): bool
    {
        return strtolower($this->getAvailability()) === 'now';
    }

    public function getCurrency(): string
    {
        return $this->data['currency'] ?? '';
    }

    public function getPrice(): string
    {
        return $this->data['price'] ?? '';
    }

    public function getPriceFloat(): float
    {
        $price = preg_replace('/[^\d.,]/', '', $this->getPrice());
        $price = str_replace(',', '.', $price);
        return (float) $price;
    }

    public function getSavings(): string
    {
        return $this->data['savings'] ?? '';
    }

    public function getSavingsPercentage(): float
    {
        return (float) ($this->data['savings_percentage'] ?? 0);
    }

    public function getSavingsBasis(): string
    {
        return $this->data['savings_basis'] ?? '';
    }

    public function isOnSale(): bool
    {
        return !empty($this->data['savings']) && (float) $this->data['savings'] > 0;
    }

    /* -------------------------------------------------------------------------
     * Prime / Shipping
     * ---------------------------------------------------------------------- */

    public function isPrime(): bool
    {
        return !empty($this->data['is_prime']);
    }

    public function isAmazonFulfilled(): bool
    {
        return !empty($this->data['is_amazon_fulfilled']);
    }

    public function getShippingCharges(): string
    {
        return $this->data['shipping_charges'] ?? '';
    }

    /* -------------------------------------------------------------------------
     * Ratings & Reviews
     * ---------------------------------------------------------------------- */

    public function getRating(): float
    {
        return (float) ($this->data['rating'] ?? 0);
    }

    public function getReviews(): int
    {
        return (int) ($this->data['reviews'] ?? 0);
    }

    public function getSalesRank(): int
    {
        return (int) ($this->data['salesrank'] ?? 0);
    }

    /* -------------------------------------------------------------------------
     * Dates
     * ---------------------------------------------------------------------- */

    public function getDateCreated(): string
    {
        return $this->data['date_created'] ?? '';
    }

    public function getDateUpdated(): string
    {
        return $this->data['date_updated'] ?? '';
    }

    /* -------------------------------------------------------------------------
     * Helpers
     * ---------------------------------------------------------------------- */

    /**
     * Get raw data array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Build product data array from Amazon API response item
     *
     * @param  array $item  Raw item from PA API response
     * @return array        Normalised data ready for DB insert/update
     */
    public static function fromApiItem(array $item): array
    {
        // Supports both Creators API v3 (lowerCamelCase) and PA-API v5 (PascalCase)
        $asin = $item['asin'] ?? $item['ASIN'] ?? '';
        $info = $item['itemInfo'] ?? $item['ItemInfo'] ?? [];
        $images = $item['images'] ?? $item['Images'] ?? [];

        // Title
        $title = $info['title']['displayValue']
              ?? $info['Title']['DisplayValue']
              ?? '';

        // URL
        $url = $item['detailPageURL'] ?? $item['DetailPageURL'] ?? '';

        // Features
        $features = $info['features']['displayValues']
                 ?? $info['Features']['DisplayValues']
                 ?? [];

        // Attributes
        $attributes = [];
        $brand = $info['byLineInfo']['brand']['displayValue']
              ?? $info['ByLineInfo']['Brand']['DisplayValue']
              ?? '';
        if ($brand) $attributes['brand'] = $brand;

        // Binding / product group
        $binding      = $info['technicalInfo']['formats']['displayValues'][0]
                     ?? $info['TechnicalInfo']['Formats']['DisplayValues'][0]
                     ?? '';
        $productGroup = $info['classifications']['productGroup']['displayValue']
                     ?? $info['Classifications']['ProductGroup']['DisplayValue']
                     ?? '';

        // Images — Creators API v3
        $imageIds = [];
        $primary  = $images['primary'] ?? $images['Primary'] ?? [];
        foreach (['small' => 'small', 'medium' => 'medium', 'large' => 'large',
                  'Small' => 'small', 'Medium' => 'medium', 'Large' => 'large'] as $key => $size) {
            $urlImg = $primary[$key]['url'] ?? $primary[$key]['URL'] ?? '';
            if ($urlImg) $imageIds[$size] = $urlImg;
        }

        // Offers — Creators API v3 uses offersV2
        $offersV2 = $item['offersV2'] ?? [];
        $listing  = $offersV2['listings'][0] ?? [];

        // Fallback to PA-API v5 style
        if (empty($listing)) {
            $offers  = $item['Offers'] ?? [];
            $listing = $offers['Listings'][0] ?? [];
        }

        // availability is an object: {"type":"Now","message":"..."} or {"type":"AvailableShipDate",...}
        $availObj     = $listing['availability'] ?? $listing['Availability'] ?? [];
        $availability = $availObj['type'] ?? $availObj['Type'] ?? '';

        $currency   = $listing['price']['currency']
                   ?? $listing['Price']['Currency']
                   ?? '';
        $price      = $listing['price']['amount']
                   ?? $listing['Price']['Amount']
                   ?? '';

        // Savings from dealDetails (replaces savingBasis which no longer exists)
        $dealDetails  = $listing['dealDetails'] ?? $listing['DealDetails'] ?? [];
        $savings      = $dealDetails['savedAmount']['amount']
                     ?? $dealDetails['SavedAmount']['Amount']
                     ?? '';
        $savingsPct   = $dealDetails['savedPercent']
                     ?? $dealDetails['SavedPercent']
                     ?? 0;
        $savingsBasis = $dealDetails['originalAmount']['amount']
                     ?? $dealDetails['OriginalAmount']['Amount']
                     ?? '';

        // Prime / fulfilled not available in this API version — default 0
        $isPrime     = 0;
        $isAmzFulfil = 0;

        // Customer reviews from dedicated field
        $rating  = $item['customerReviews']['starRating']['value']
                ?? $item['CustomerReviews']['StarRating']['Value']
                ?? '';
        $reviews = $item['customerReviews']['count']['value']
                ?? $item['CustomerReviews']['Count']['Value']
                ?? 0;

        // Sales rank
        $nodes     = $item['browseNodeInfo']['browseNodes']
                  ?? $item['BrowseNodeInfo']['BrowseNodes']
                  ?? [];
        $salesRank = $nodes[0]['salesRank'] ?? $nodes[0]['SalesRank'] ?? 0;

        return [
            'status'              => 'active',
            'asin'                => $asin,
            'title'               => $title,
            'url'                 => $url,
            'features'            => json_encode($features),
            'attributes'          => json_encode($attributes),
            'binding'             => $binding,
            'product_group'       => $productGroup,
            'image_ids'           => json_encode($imageIds),
            'availability'        => $availability,
            'currency'            => $currency,
            'price'               => $price !== '' ? (string) $price : '',
            'savings'             => $savings !== '' ? (string) $savings : '',
            'savings_percentage'  => (float) $savingsPct,
            'savings_basis'       => $savingsBasis !== '' ? (string) $savingsBasis : '',
            'salesrank'           => (float) $salesRank,
            'is_prime'            => $isPrime,
            'is_amazon_fulfilled' => $isAmzFulfil,
            'rating'              => (string) $rating,
            'reviews'             => (int) $reviews,
            'date_created'        => date('Y-m-d H:i:s'),
            'date_updated'        => date('Y-m-d H:i:s'),
        ];
    }
}
