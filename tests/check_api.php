<?php
// Self-check, no Joomla needed:  php tests/check_api.php
// Item shape = Creators API JSON, field paths from the official PHP SDK 1.3.0 models (AAWP 5.1.2).
define('_JEXEC', 1);
require __DIR__ . '/../component/admin/classes/Product/AajProduct.php';
require __DIR__ . '/../component/admin/classes/Api/AmazonApi.php';

use Aaj\Component\Aaj\Administrator\Product\AajProduct;
use Aaj\Component\Aaj\Administrator\Api\AmazonApi;

$item = [
    'asin' => 'B000TEST01',
    'detailPageURL' => 'https://www.amazon.fr/dp/B000TEST01?tag=x-21',
    'itemInfo' => [
        'title' => ['displayValue' => 'Produit test'],
        'features' => ['displayValues' => ['A', 'B']],
        'byLineInfo' => ['brand' => ['displayValue' => 'Marque']],
    ],
    'images' => ['primary' => ['medium' => ['url' => 'https://m.media-amazon.com/m.jpg']]],
    'offersV2' => ['listings' => [
        ['isBuyBoxWinner' => true, 'merchantInfo' => ['id' => 'A1X6FK5RDHNB96'],
         'price' => ['money' => ['amount' => 19.99, 'currency' => 'EUR'],
                     'savings' => ['money' => ['amount' => 5.0], 'percentage' => 20],
                     'savingBasis' => ['money' => ['amount' => 24.99]]]],
        ['availability' => ['type' => 'IN_STOCK_SCARCE']], // availability only on 2nd listing
    ]],
    'customerReviews' => ['count' => 1234, 'starRating' => ['value' => 4.5]],
    'browseNodeInfo' => ['browseNodes' => [['salesRank' => 7]]],
];

$d = AajProduct::fromApiItem($item);
assert($d['price'] === '19.99' && $d['currency'] === 'EUR');
assert($d['savings'] === '5' && $d['savings_percentage'] === 20.0 && $d['savings_basis'] === '24.99');
assert($d['availability'] === 'in_stock');
assert($d['is_prime'] === 1 && $d['is_amazon_fulfilled'] === 0);
assert($d['reviews'] === 1234 && $d['rating'] === '4.5' && $d['salesrank'] === 7.0);
assert(!array_key_exists('binding', $d) && !array_key_exists('product_group', $d)); // not in DB schema
assert((new AajProduct($d))->isAvailable());
assert(!(new AajProduct(['availability' => 'out_of_stock']))->isAvailable());

$empty = AajProduct::fromApiItem(['asin' => 'X']);
assert($empty['availability'] === 'out_of_stock' && $empty['is_prime'] === 0 && $empty['price'] === '');

// Credential version: major from credential type, region from marketplace
$ver = fn(AmazonApi $a) => (fn() => $this->credVersion)->call($a);
assert($ver(new AmazonApi('amzn1.application-oa2-client.x', 's', 't', 'fr')) === '3.2');
assert($ver(new AmazonApi('amzn1.application-oa2-client.x', 's', 't', 'com')) === '3.1');
assert($ver(new AmazonApi('7abc123cognito', 's', 't', 'de')) === '2.2');
assert($ver(new AmazonApi('7abc123cognito', 's', 't', 'co.jp')) === '2.3');

// Lists: SearchItems paging (itemPage), NewestArrivals sort, numeric category = browse node
$stub = new class('amzn1.x', 's', 't', 'fr') extends AmazonApi {
    public array $calls = [];
    public function searchItems(string $keywords, string $searchIndex = 'All', int $count = 10,
        int $page = 1, string $sortBy = '', string $browseNodeId = ''): ?array {
        $this->calls[] = [$keywords, $page, $sortBy, $browseNodeId];
        return array_map(fn($i) => ['asin' => "P{$page}_{$i}"], range(1, 10));
    }
};
$items = $stub->getListItems('2454160031', 'NewReleases', 15);
assert(count($items) === 15 && $items[10]['asin'] === 'P2_1');
assert($stub->calls === [['', 1, 'NewestArrivals', '2454160031'], ['', 2, 'NewestArrivals', '2454160031']]);
$stub->calls = [];
$stub->getListItems('casque bluetooth', 'BestSellers', 5);
assert($stub->calls === [['casque bluetooth', 1, '', '']]);

echo "OK\n";
