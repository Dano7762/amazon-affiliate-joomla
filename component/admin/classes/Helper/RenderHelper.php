<?php
/**
 * Render helper — generates HTML output for AAJ shortcodes
 *
 * @package Aaj\Component\Aaj
 */

namespace Aaj\Component\Aaj\Administrator\Helper;

defined('_JEXEC') or die;

use Aaj\Component\Aaj\Administrator\Product\AajProduct;
use Joomla\CMS\Uri\Uri;

/**
 * Provides rendering helpers consumed by templates
 */
class RenderHelper
{
    /** @var array Config snapshot */
    private array $config;

    /** @var string Amazon store TLD */
    private string $store;

    /** @var string Tracking ID */
    private string $trackingId;

    /** @var array Shortcode attributes */
    public array $atts = [];

    /** @var AajProduct[] */
    public array $items = [];

    /** @var AajProduct */
    public AajProduct $item;

    /** @var int Current item index */
    public int $itemIndex = 0;

    /** @var string current function type */
    public string $type = 'box';

    public function __construct(array $config)
    {
        $this->config     = $config;
        $this->store      = $config['api_country'] ?? 'com';
        $this->trackingId = $config['api_tag'] ?? '';
    }

    /* -------------------------------------------------------------------------
     * Item title
     * ---------------------------------------------------------------------- */

    public function getProductTitle(): string
    {
        $limit = (int) ($this->atts['title_length'] ?? $this->config['title_length'] ?? 100);
        return $this->item->getTitle($limit);
    }

    /* -------------------------------------------------------------------------
     * Item URL / Links
     * ---------------------------------------------------------------------- */

    public function getProductUrl(string $type = 'basic'): string
    {
        return $this->item->buildUrl($type, $this->trackingId);
    }

    public function getProductLinkTitle(): string
    {
        return htmlspecialchars($this->item->getRawTitle(), ENT_QUOTES, 'UTF-8');
    }

    /* -------------------------------------------------------------------------
     * Images
     * ---------------------------------------------------------------------- */

    public function getProductImage(string $size = 'medium'): string
    {
        $img = $this->item->getImage($size);
        if (!$img) {
            $img = Uri::root() . 'media/com_aaj/img/placeholder-' . $size . '.jpg';
        }
        return $img;
    }

    public function getProductImageLink(): string
    {
        return $this->getProductUrl();
    }

    public function getProductImageAlt(): string
    {
        return $this->getProductLinkTitle();
    }

    /* -------------------------------------------------------------------------
     * Description
     * ---------------------------------------------------------------------- */

    public function getProductDescription(): string
    {
        $items  = (int) ($this->atts['description_items'] ?? $this->config['description_items'] ?? 5);
        $limit  = (int) ($this->atts['description_length'] ?? $this->config['description_length'] ?? 0);
        $desc   = $this->item->getDescription($items, $limit);
        return nl2br(htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'));
    }

    /* -------------------------------------------------------------------------
     * Pricing
     * ---------------------------------------------------------------------- */

    public function getProductPricing(string $type = 'current'): string
    {
        if ($type === 'old') {
            $amount = $this->item->getSavingsBasis();
        } else {
            $amount = $this->item->getPrice();
        }

        if ($amount === '' || $amount === null) {
            return '';
        }

        return $this->formatPrice((float) $amount, $this->item->getCurrency());
    }

    public function productIsOnSale(): bool
    {
        return $this->item->isOnSale();
    }

    public function getSavedText(): string
    {
        $type = $this->config['pricing_reduction'] ?? 'amount';

        if ($type === 'percentage' && $this->item->getSavingsPercentage() > 0) {
            return '-' . round($this->item->getSavingsPercentage()) . '%';
        }

        $savings = $this->item->getSavings();
        if ($savings) {
            return '-' . $this->formatPrice((float) $savings, $this->item->getCurrency());
        }

        return '';
    }

    public function showAdvertisedPrice(): bool
    {
        return $this->item->getPrice() !== '';
    }

    private function formatPrice(float $amount, string $currency): string
    {
        $symbols = [
            'EUR' => '€', 'USD' => '$', 'GBP' => '£', 'JPY' => '¥',
            'CAD' => 'CA$', 'AUD' => 'A$', 'BRL' => 'R$', 'MXN' => 'MX$',
            'INR' => '₹', 'CNY' => '¥', 'SEK' => 'kr', 'PLN' => 'zł',
        ];
        $symbol = $symbols[$currency] ?? $currency . ' ';
        return $symbol . number_format($amount, 2, ',', '.');
    }

    /* -------------------------------------------------------------------------
     * Prime
     * ---------------------------------------------------------------------- */

    public function getProductCheckPrime(): string
    {
        if (!$this->item->isPrime()) {
            return '';
        }

        $type = $this->config['check_prime'] ?? 'linked';
        $imgUrl = Uri::root() . 'media/com_aaj/img/icon-check-prime.png';

        if ($type === 'linked') {
            $primeUrl = 'https://www.amazon.' . $this->store . '/prime?tag=' . $this->trackingId;
            return '<a href="' . $primeUrl . '" target="_blank" rel="nofollow noopener sponsored">'
                . '<img class="aaj-product__prime" src="' . $imgUrl . '" alt="Prime" />'
                . '</a>';
        }

        return '<img class="aaj-product__prime" src="' . $imgUrl . '" alt="Prime" />';
    }

    /* -------------------------------------------------------------------------
     * Ratings
     * ---------------------------------------------------------------------- */

    public function getProductRating(): float
    {
        return $this->item->getRating();
    }

    public function getProductReviews(): int
    {
        return $this->item->getReviews();
    }

    public function getProductStarRating(): string
    {
        $rating = $this->item->getRating();
        if (!$rating) {
            return '';
        }

        $stars     = round($rating * 2) / 2; // Round to nearest 0.5
        $width     = ($stars / 5) * 100;
        $reviewUrl = $this->getProductUrl('reviews');

        return '<a href="' . $reviewUrl . '" target="_blank" rel="nofollow noopener sponsored">'
            . '<span class="aaj-product__stars" title="' . number_format($rating, 1) . '/5">'
            . '<span class="aaj-product__stars--filled" style="width:' . $width . '%"></span>'
            . '</span>'
            . '</a>';
    }

    /* -------------------------------------------------------------------------
     * Buttons
     * ---------------------------------------------------------------------- */

    public function getButton(string $type = 'buy'): string
    {
        $style = $this->atts['button_style'] ?? $this->config['button_style'] ?? 'amazon';
        $icon  = $this->config['button_icon'] ?? 'black';

        if ($type === 'detail') {
            $url  = $this->getProductUrl();
            $text = !empty($this->config['button_text']) ? $this->config['button_text'] : 'Voir sur Amazon';
            $cls  = 'aaj-product__btn aaj-product__btn--detail aaj-product__btn--' . $style;
        } else {
            $url  = $this->getProductUrl('cart');
            $text = 'Ajouter au panier';
            $cls  = 'aaj-product__btn aaj-product__btn--cart aaj-product__btn--' . $style;
        }

        $iconHtml = '';
        if ($style === 'amazon') {
            $iconUrl  = Uri::root() . 'media/com_aaj/img/icon-amazon-' . $icon . '.svg';
            $iconHtml = '<img src="' . $iconUrl . '" alt="" class="aaj-product__btn-icon" />';
        }

        return '<a href="' . $url . '" class="' . $cls . '" target="_blank" rel="nofollow noopener sponsored">'
            . $iconHtml . htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
            . '</a>';
    }

    /* -------------------------------------------------------------------------
     * Ribbons / Badges
     * ---------------------------------------------------------------------- */

    public function getProductRibbons(): string
    {
        $ribbons = '';

        if ($this->item->getSalesRank() === 1) {
            $ribbons .= '<span class="aaj-product__ribbon aaj-product__ribbon--bestseller">#1 Bestseller</span>';
        }

        if ($this->productIsOnSale() && $this->item->getSavingsPercentage() >= 20) {
            $pct = round($this->item->getSavingsPercentage());
            $ribbons .= '<span class="aaj-product__ribbon aaj-product__ribbon--sale">-' . $pct . '%</span>';
        }

        if ($this->item->isPrime()) {
            // Prime badge handled separately
        }

        return $ribbons;
    }

    /* -------------------------------------------------------------------------
     * Container helpers
     * ---------------------------------------------------------------------- */

    /**
     * Get CSS classes for the product container
     */
    public function getProductContainerClasses(string $base = 'aaj-product'): string
    {
        $classes = [$base];

        if ($this->item->isOnSale()) {
            $classes[] = 'aaj-product--sale';
        }
        if (!$this->item->isAvailable()) {
            $classes[] = 'aaj-product--unavailable';
        }
        if ($this->item->isPrime()) {
            $classes[] = 'aaj-product--prime';
        }

        return implode(' ', $classes);
    }

    /**
     * Print data-asin attribute for tracking
     */
    public function getProductContainerData(): string
    {
        return 'data-asin="' . htmlspecialchars($this->item->getAsin(), ENT_QUOTES, 'UTF-8') . '"';
    }

    /* -------------------------------------------------------------------------
     * Disclaimer
     * ---------------------------------------------------------------------- */

    public function getDisclaimer(): string
    {
        $text = $this->config['disclaimer'] ?? '';
        if (!$text) {
            return '';
        }
        return '<p class="aaj-disclaimer">' . htmlspecialchars(stripslashes($text), ENT_QUOTES, 'UTF-8') . '</p>';
    }
}
