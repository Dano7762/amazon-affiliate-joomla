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
        // 'in_stock' = normalised value; raw Creators types / legacy 'now' may still sit in old cache rows
        return in_array(strtolower($this->getAvailability()), ['in_stock', 'in_stock_scarce', 'instock', 'now'], true);
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
     * Build product data array from a Creators API item (JSON, lowerCamelCase)
     *
     * Field paths follow the official Creators API PHP SDK models (as used by AAWP 5.1.2).
     *
     * @param  array $item  Raw item from the Creators API response
     * @return array        Normalised data ready for DB insert/update
     */
    public static function fromApiItem(array $item): array
    {
        $info   = $item['itemInfo'] ?? [];
        $images = $item['images']['primary'] ?? [];

        $attributes = [];
        if ($brand = $info['byLineInfo']['brand']['displayValue'] ?? '') {
            $attributes['brand'] = $brand;
        }

        $imageIds = [];
        foreach (['small', 'medium', 'large'] as $size) {
            if ($urlImg = $images[$size]['url'] ?? '') {
                $imageIds[$size] = $urlImg;
            }
        }

        // Offers: price/savings live under listing.price.{money,savings,savingBasis}
        $listings = $item['offersV2']['listings'] ?? [];
        $listing  = $listings[0] ?? [];
        $price    = $listing['price'] ?? [];

        // Availability: first listing that has one (AAWP 5.1.0 fix), IN_STOCK* => in_stock
        $availability = 'out_of_stock';
        foreach ($listings as $l) {
            if (!empty($l['availability']['type'])) {
                $availability = in_array($l['availability']['type'], ['IN_STOCK', 'IN_STOCK_SCARCE', 'INSTOCKSCARCE', 'INSTOCK'], true)
                    ? 'in_stock' : 'out_of_stock';
                break;
            }
        }

        $nodes = $item['browseNodeInfo']['browseNodes'] ?? [];
        $now   = date('Y-m-d H:i:s');

        return [
            'status'              => 'active',
            'asin'                => $item['asin'] ?? '',
            'title'               => $info['title']['displayValue'] ?? '',
            'url'                 => $item['detailPageURL'] ?? '',
            'features'            => json_encode($info['features']['displayValues'] ?? []),
            'attributes'          => json_encode($attributes),
            'image_ids'           => json_encode($imageIds),
            'availability'        => $availability,
            'currency'            => (string) ($price['money']['currency'] ?? ''),
            'price'               => (string) ($price['money']['amount'] ?? ''),
            'savings'             => (string) ($price['savings']['money']['amount'] ?? ''),
            'savings_percentage'  => (float) ($price['savings']['percentage'] ?? 0),
            'savings_basis'       => (string) ($price['savingBasis']['money']['amount'] ?? ''),
            'salesrank'           => (float) ($nodes[0]['salesRank'] ?? 0),
            'is_prime'            => self::detectPrime($listings),
            'is_amazon_fulfilled' => ($listing['merchantInfo']['id'] ?? '') === 'ATVPDKIKX0DER' ? 1 : 0,
            'rating'              => (string) ($item['customerReviews']['starRating']['value'] ?? ''),
            'reviews'             => (int) ($item['customerReviews']['count'] ?? 0),
            'date_created'        => $now,
            'date_updated'        => $now,
        ];
    }

    /**
     * Prime heuristic from AAWP 5.1.2 (the Creators API has no explicit Prime flag):
     * buy-box listing that is a Prime deal or is sold by Amazon itself.
     */
    private static function detectPrime(array $listings): int
    {
        // ponytail: AAWP's merchant list; extend if a marketplace's Amazon seller ID is missing
        $amazonMerchants = ['ATVPDKIKX0DER', 'A3P5ROKL5A1OLE', 'A3JWKAKR8XB7XF', 'A1X6FK5RDHNB96', 'APJ6JRA9NG5V4',
            'A1AT7YVPFBWXBL', 'AN1VRQENFRJN5', 'A3DWYIK6Y9EEQB', 'A39IBJ37TRP1C6', 'A21TJRUUN4KGV', 'A13V1IB3VIYZZH'];

        foreach ($listings as $l) {
            if (empty($l['isBuyBoxWinner'])) {
                continue;
            }
            if (in_array($l['dealDetails']['accessType'] ?? '', ['PRIME_EXCLUSIVE', 'PRIME_EARLY_ACCESS'], true)
                || in_array($l['merchantInfo']['id'] ?? '', $amazonMerchants, true)) {
                return 1;
            }
        }
        return 0;
    }
}
