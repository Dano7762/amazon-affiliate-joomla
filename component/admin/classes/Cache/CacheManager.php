<?php
/**
 * AAJ Cache Manager
 * Handles product and list cache renewal using the Amazon Creators API
 *
 * @package Aaj\Component\Aaj
 */

namespace Aaj\Component\Aaj\Administrator\Cache;

defined('_JEXEC') or die;

use Aaj\Component\Aaj\Administrator\Api\AmazonApi;
use Aaj\Component\Aaj\Administrator\Database\ProductsTable;
use Aaj\Component\Aaj\Administrator\Database\ListsTable;
use Aaj\Component\Aaj\Administrator\Product\AajProduct;
use Aaj\Component\Aaj\Administrator\Helper\ConfigHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

/**
 * Renews outdated product and list caches from Amazon API
 */
class CacheManager
{
    /** @var AmazonApi */
    private AmazonApi $api;

    /** @var ProductsTable */
    private ProductsTable $productsDb;

    /** @var ListsTable */
    private ListsTable $listsDb;

    /** @var array */
    private array $config;

    /** @var int */
    private int $maxProductRenewals = 25;

    /** @var int */
    private int $maxListRenewals = 10;

    /** @var array */
    private array $log = [];

    public function __construct()
    {
        $this->config     = ConfigHelper::getAll();
        $this->productsDb = new ProductsTable();
        $this->listsDb    = new ListsTable();
        $this->api        = ConfigHelper::buildApi();
    }

    /* -------------------------------------------------------------------------
     * Public API
     * ---------------------------------------------------------------------- */

    /**
     * Renew all outdated products and lists
     */
    public function renew(): array
    {
        $this->setupMaxRenewals();

        $productsRenewed = $this->renewProducts();
        $listsRenewed    = $this->renewLists();

        $this->log[] = "Products renewed: {$productsRenewed}";
        $this->log[] = "Lists renewed: {$listsRenewed}";

        // Update last run timestamp
        $params = ConfigHelper::getComponent();
        $params->set('cache_last_run', Factory::getDate()->toSql());
        ConfigHelper::saveComponent($params);

        return $this->log;
    }

    /**
     * Force renew specific ASINs
     *
     * @param  string[] $asins
     * @return int  Number renewed
     */
    public function renewAsins(array $asins): int
    {
        return $this->fetchAndStoreProducts($asins);
    }

    /**
     * Renew a single product by ID
     */
    public function renewProduct(int $productId): bool
    {
        $product = $this->productsDb->getById($productId);
        if (!$product || empty($product['asin'])) {
            return false;
        }
        return $this->fetchAndStoreProducts([$product['asin']]) > 0;
    }

    /* -------------------------------------------------------------------------
     * Internal
     * ---------------------------------------------------------------------- */

    /**
     * Determine max renewal batch sizes based on DB size
     */
    private function setupMaxRenewals(): void
    {
        $productCount = $this->productsDb->count();
        $listCount    = $this->listsDb->count();

        if ($productCount > 1000) {
            $this->maxProductRenewals = 50;
        }
        if ($listCount > 100) {
            $this->maxListRenewals = 15;
        }
    }

    /**
     * Renew outdated product rows
     */
    private function renewProducts(): int
    {
        $cacheDuration = (int) ($this->config['cache_duration'] ?? 720);

        $outdated = $this->productsDb->getProducts([
            'outdated'       => true,
            'cache_duration' => $cacheDuration,
            'orderby'        => 'date_updated',
            'order'          => 'ASC',
            'limit'          => $this->maxProductRenewals,
        ]);

        if (empty($outdated)) {
            $this->log[] = 'No outdated products found.';
            return 0;
        }

        $asins = array_column($outdated, 'asin');
        return $this->fetchAndStoreProducts($asins);
    }

    /**
     * Fetch products from API and store in DB (in batches of 10)
     */
    private function fetchAndStoreProducts(array $asins): int
    {
        $renewed = 0;
        $batches = array_chunk(array_unique($asins), 10);

        foreach ($batches as $batch) {
            $items = $this->api->getItems($batch);

            if (!$items) {
                $errors = $this->api->getErrors();
                foreach ($errors as $err) {
                    $this->log[] = 'API Error: [' . $err['Code'] . '] ' . $err['Message'];
                }
                continue;
            }

            foreach ($items as $item) {
                $data = AajProduct::fromApiItem($item);
                if ($data['asin']) {
                    $this->productsDb->upsert($data);
                    $renewed++;
                }
            }
        }

        return $renewed;
    }

    /**
     * Renew outdated list rows
     */
    private function renewLists(): int
    {
        $cacheDuration = (int) ($this->config['cache_duration'] ?? 720);

        $outdated = $this->listsDb->getLists([
            'outdated'       => true,
            'cache_duration' => $cacheDuration,
            'orderby'        => 'date_updated',
            'order'          => 'ASC',
            'limit'          => $this->maxListRenewals,
        ]);

        if (empty($outdated)) {
            $this->log[] = 'No outdated lists found.';
            return 0;
        }

        $renewed = 0;

        foreach ($outdated as $list) {
            $type     = $list['type'];
            $category = $list['category'] ?? '';
            $itemCount = 10;

            $apiType = ($type === 'new_releases') ? 'NewReleases' : 'BestSellers';
            $items   = $this->api->getListItems($category, $apiType, $itemCount);

            if (!$items) {
                $this->log[] = "Could not renew list ID {$list['id']} (type: {$type}, category: {$category})";
                continue;
            }

            $products = [];
            foreach ($items as $item) {
                $data = AajProduct::fromApiItem($item);
                if ($data['asin']) {
                    $pid = $this->productsDb->upsert($data);
                    $products[] = ['asin' => $data['asin'], 'id' => $pid];
                }
            }

            $this->listsDb->update((int) $list['id'], [
                'products' => json_encode($products),
            ]);

            $renewed++;
        }

        return $renewed;
    }
}
