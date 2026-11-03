<?php
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;

// Register PSR-4 autoloader for com_aaj classes
$compSrc = JPATH_ADMINISTRATOR . '/components/com_aaj/classes';
if (is_dir($compSrc)) {
    spl_autoload_register(function (string $class) use ($compSrc): void {
        $prefix = 'Aaj\\Component\\Aaj\\Administrator\\';
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) return;
        $file = $compSrc . DIRECTORY_SEPARATOR
              . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) require_once $file;
    });
}

class PlgContentAaj extends CMSPlugin
{
    protected $autoloadLanguage = true;

    public function onContentPrepare($context, &$article, &$params, $page = 0): void
    {
        if (!isset($article->text)) {
            return;
        }

        // Handle HTML-encoded braces that editors like JCE may produce
        $text = html_entity_decode($article->text, ENT_QUOTES, 'UTF-8');

        if (strpos($text, '{aaj') === false) {
            return;
        }

        $article->text = $this->processShortcodes($article->text);
    }

    private function processShortcodes(string $content): string
    {
        // Decode HTML entities so the regex can match regardless of editor encoding
        $decoded = html_entity_decode($content, ENT_QUOTES, 'UTF-8');

        $result = preg_replace_callback(
            '/\{aaj([^}]*)\}/',
            function (array $m): string {
                return $this->renderShortcode($this->parseAtts($m[1]));
            },
            $decoded
        );

        return $result ?? $content;
    }

    private function parseAtts(string $raw): array
    {
        $atts = [];
        // Match both straight quotes and HTML-encoded quotes (&quot;)
        preg_match_all('/(\w+)\s*=\s*(?:["\']|&quot;)([^"\'&]*)(?:["\']|&quot;)/', $raw, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $atts[strtolower($m[1])] = $m[2];
        }
        return $atts;
    }

    private function renderShortcode(array $atts): string
    {
        $type = strtolower($atts['type'] ?? 'box');
        $this->enqueueAssets();

        switch ($type) {
            case 'box':          return $this->renderBox($atts);
            case 'link':         return $this->renderLink($atts);
            case 'bestseller':   return $this->renderList($atts, 'bestseller');
            case 'new_releases': return $this->renderList($atts, 'new_releases');
            case 'field':        return $this->renderField($atts);
            default:             return '';
        }
    }

    private function renderBox(array $atts): string
    {
        $asins = array_filter(array_map('trim', explode(',', $atts['asin'] ?? $atts['id'] ?? '')));
        if (empty($asins)) return $this->debugMsg('renderBox: no ASIN found in atts');

        $products = [];
        $fallbacks = [];
        foreach ($asins as $asin) {
            $row = $this->getOrFetch($asin);
            if ($row) {
                $products[] = $row;
            } else {
                $fallbacks[] = $asin;
            }
        }

        $out = '';
        if (!empty($products)) {
            $template = $atts['template'] ?? $this->getParam('box_template', 'horizontal');
            $out .= $this->renderProductLoop($products, 'box', $template, $atts);
        }
        if (!empty($fallbacks)) {
            foreach ($fallbacks as $asin) {
                $out .= $this->renderAsinFallback($asin);
            }
        }
        return $out;
    }

    private function renderLink(array $atts): string
    {
        $asin = trim($atts['asin'] ?? $atts['id'] ?? '');
        if (!$asin) return '';
        $row = $this->getOrFetch($asin);
        if (!$row) return '';
        $title = htmlspecialchars($atts['title'] ?? $row['title'] ?? $asin, ENT_QUOTES);
        $url   = $this->buildUrl($row, 'basic');
        return "<a class=\"aaj-link\" href=\"{$url}\" target=\"_blank\" rel=\"nofollow noopener sponsored\">{$title}</a>";
    }

    private function renderList(array $atts, string $type): string
    {
        $category  = trim($atts['category'] ?? $atts['keywords'] ?? ''); // numeric = browse node, else keywords
        $itemCount = min((int)($atts['items'] ?? 10), 10);
        if (!$category) return '';

        $rows = $this->getCachedList($type, $category, $itemCount);
        if (empty($rows)) return '';

        $template = $atts['template'] ?? $this->getParam($type . '_template', 'horizontal');
        return $this->renderProductLoop($rows, $type, $template, $atts);
    }

    private function renderField(array $atts): string
    {
        $asin  = trim($atts['asin'] ?? '');
        $field = $atts['field'] ?? 'price';
        if (!$asin) return '';
        $row = $this->getOrFetch($asin);
        if (!$row) return '';
        switch ($field) {
            case 'price':   return htmlspecialchars(($row['currency'] ?? '') . ' ' . ($row['price'] ?? ''));
            case 'title':   return htmlspecialchars($row['title'] ?? '');
            case 'url':     return $this->buildUrl($row, 'basic');
            case 'rating':  return $row['rating'] ?? '';
            case 'reviews': return (string)($row['reviews'] ?? '');
            case 'prime':   return $row['is_prime'] ? 'Yes' : 'No';
            case 'image':
                $imgs = json_decode($row['image_ids'] ?? '{}', true);
                $src  = $imgs[$atts['size'] ?? 'medium'] ?? '';
                return $src ? "<img src=\"{$src}\" alt=\"\" />" : '';
            default: return '';
        }
    }

    private function renderProductLoop(array $rows, string $type, string $template, array $atts): string
    {
        $tmplFile = JPATH_PLUGINS . "/content/aaj/tmpl/{$type}/{$template}.php";
        if (!is_file($tmplFile)) {
            $tmplFile = JPATH_PLUGINS . "/content/aaj/tmpl/{$type}/horizontal.php";
        }

        $store  = $this->getParam('api_country', 'com');
        $config = [
            'api_country'        => $store,
            'api_tag'            => $this->getParam('api_tag', ''),
            'title_length'       => $this->getParam('title_length', 100),
            'description_items'  => $this->getParam('description_items', 5),
            'description_length' => $this->getParam('description_length', 0),
            'pricing_reduction'  => $this->getParam('pricing_reduction', 'amount'),
            'check_prime'        => $this->getParam('check_prime', 'linked'),
            'button_style'       => $this->getParam('button_style', 'amazon'),
            'button_icon'        => $this->getParam('button_icon', 'black'),
            'button_text'        => $this->getParam('button_text', ''),
            'disclaimer'         => $this->getParam('disclaimer', ''),
        ];

        $out = "<div class=\"aaj aaj--{$type}\">";
        foreach ($rows as $row) {
            if (is_file($tmplFile)) {
                ob_start();
                $r = new \Aaj\Component\Aaj\Administrator\Helper\RenderHelper($config);
                $r->item  = new \Aaj\Component\Aaj\Administrator\Product\AajProduct($row, $store);
                $r->atts  = $atts;
                $r->type  = $type;
                include $tmplFile;
                $out .= ob_get_clean();
            } else {
                $out .= $this->fallback($row, $atts);
            }
        }
        $out .= '</div>';

        $disclaimer = $config['disclaimer'];
        if ($disclaimer) {
            $out .= '<p class="aaj-disclaimer">'
                  . htmlspecialchars(stripslashes($disclaimer), ENT_QUOTES, 'UTF-8')
                  . '</p>';
        }

        return $out;
    }

    private function fallback(array $row, array $atts): string
    {
        $url   = $this->buildUrl($row, 'basic');
        $title = htmlspecialchars($row['title'] ?? '', ENT_QUOTES);
        $imgs  = json_decode($row['image_ids'] ?? '{}', true);
        $img   = $imgs['medium'] ?? $imgs['small'] ?? '';
        $price = ($row['currency'] ?? '') . ' ' . ($row['price'] ?? '');
        return "<div class=\"aaj-product aaj-product--horizontal\" data-asin=\"{$row['asin']}\">"
             . "<div class=\"aaj-product__thumb\"><a href=\"{$url}\" target=\"_blank\" rel=\"nofollow noopener sponsored\">"
             . ($img ? "<img src=\"{$img}\" class=\"aaj-product__image\" alt=\"{$title}\">" : '')
             . "</a></div>"
             . "<div class=\"aaj-product__content\"><a class=\"aaj-product__title\" href=\"{$url}\" target=\"_blank\" rel=\"nofollow noopener sponsored\">{$title}</a></div>"
             . "<div class=\"aaj-product__footer\"><span class=\"aaj-product__price\">" . htmlspecialchars($price) . "</span>"
             . "<a href=\"{$url}\" class=\"aaj-product__btn aaj-product__btn--detail aaj-product__btn--amazon\" target=\"_blank\" rel=\"nofollow noopener sponsored\">Voir sur Amazon</a></div>"
             . "</div>";
    }

    private function getOrFetch(string $asin): ?array
    {
        $row = null;
        try {
            $db  = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $q   = $db->getQuery(true)->select('*')->from('#__aaj_products')
                      ->where('asin = ' . $db->quote($asin))->setLimit(1);
            $db->setQuery($q);
            $row = $db->loadAssoc();

            if ($row) {
                $cache = (int)$this->getParam('cache_duration', 720);
                if (time() < strtotime($row['date_updated']) + $cache * 3600) {
                    return $row;
                }
            }
        } catch (\Exception $e) {
            \Joomla\CMS\Log\Log::add('AAJ getOrFetch DB error: ' . $e->getMessage(), \Joomla\CMS\Log\Log::ERROR, 'com_aaj');
            return null;
        }

        $fetched = $this->fetchFromApi([$asin]);
        \Joomla\CMS\Log\Log::add('AAJ fetchFromApi result count for ' . $asin . ': ' . count($fetched), \Joomla\CMS\Log\Log::DEBUG, 'com_aaj');
        return $fetched[0] ?? $row;
    }

    private function renderAsinFallback(string $asin): string
    {
        $tag  = $this->getParam('api_tag', '');
        $tld  = $this->getParam('api_country', 'fr');
        $url  = "https://www.amazon.{$tld}/dp/{$asin}" . ($tag ? "?tag={$tag}" : '');

        // No reliable image URL without API — use local placeholder
        $img  = \Joomla\CMS\Uri\Uri::root() . 'media/com_aaj/img/placeholder-medium.jpg';

        $btnText = $this->getParam('button_text', '') ?: 'Voir sur Amazon';
        $tldUp   = strtoupper($tld);

        return '<div class="aaj aaj--box">'
             . '<div class="aaj-product aaj-product--horizontal aaj-product--fallback" data-asin="' . htmlspecialchars($asin, ENT_QUOTES) . '">'
             . '<div class="aaj-product__thumb">'
             . '<a href="' . $url . '" target="_blank" rel="nofollow noopener sponsored">'
             . '<img class="aaj-product__image" src="' . $img . '" alt="' . htmlspecialchars($asin, ENT_QUOTES) . '" />'
             . '</a>'
             . '</div>'
             . '<div class="aaj-product__content">'
             . '<p class="aaj-product__title">'
             . '<a href="' . $url . '" target="_blank" rel="nofollow noopener sponsored">'
             . 'Voir le produit sur Amazon ' . $tldUp
             . '</a>'
             . '</p>'
             . '</div>'
             . '<div class="aaj-product__footer">'
             . '<span class="aaj-product__price">Prix disponible sur Amazon</span>'
             . '<a href="' . $url . '" class="aaj-product__btn aaj-product__btn--detail aaj-product__btn--amazon" target="_blank" rel="nofollow noopener sponsored">'
             . htmlspecialchars($btnText, ENT_QUOTES)
             . '</a>'
             . '</div>'
             . '</div>'
             . '</div>';
    }

    private function debugMsg(string $msg): string
    {
        \Joomla\CMS\Log\Log::add('AAJ: ' . $msg, \Joomla\CMS\Log\Log::WARNING, 'com_aaj');
        $app = Factory::getApplication();
        if ($app->get('debug', false)) {
            return '<!-- AAJ DEBUG: ' . htmlspecialchars($msg) . ' -->';
        }
        return '';
    }

    private function fetchFromApi(array $asins): array
    {
        try {
            $key     = $this->getParam('api_key', '');
            $secret  = $this->getParam('api_secret', '');
            $tag     = $this->getParam('api_tag', '');
            $country = $this->getParam('api_country', 'com');

            if (!$key || !$secret || !$tag) {
                \Joomla\CMS\Log\Log::add('AAJ fetchFromApi: missing credentials key=' . (int)(bool)$key . ' secret=' . (int)(bool)$secret . ' tag=' . (int)(bool)$tag, \Joomla\CMS\Log\Log::WARNING, 'com_aaj');
                return [];
            }

            $api   = \Aaj\Component\Aaj\Administrator\Api\AmazonApi::create($key, $secret, $tag, $country);
            $items = $api->getItems($asins);

            if (!$items) {
                $errors = $api->getErrors();
                \Joomla\CMS\Log\Log::add('AAJ getItems returned null. Errors: ' . json_encode($errors), \Joomla\CMS\Log\Log::WARNING, 'com_aaj');
                return [];
            }

            $db      = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $results = [];
            foreach ($items as $item) {
                $data = \Aaj\Component\Aaj\Administrator\Product\AajProduct::fromApiItem($item);
                if (!$data['asin']) continue;
                $this->upsertProduct($db, $data);
                $results[] = $data;
            }
            return $results;
        } catch (\Exception $e) {
            \Joomla\CMS\Log\Log::add('AAJ fetchFromApi exception: ' . $e->getMessage(), \Joomla\CMS\Log\Log::ERROR, 'com_aaj');
            return [];
        }
    }

    private function getCachedList(string $type, string $category, int $count): array
    {
        try {
            $db      = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $country = $this->getParam('api_country', 'com');
            $key     = $type . '_' . md5($type . $category . $country . $count);
            $q       = $db->getQuery(true)->select('*')->from('#__aaj_lists')
                          ->where('list_key = ' . $db->quote($key))->setLimit(1);
            $db->setQuery($q);
            $list = $db->loadAssoc();

            if ($list) {
                $cache  = (int)$this->getParam('cache_duration', 720);
                $expiry = strtotime($list['date_updated']) + $cache * 3600;
                if (time() < $expiry) {
                    $stored = json_decode($list['products'] ?? '[]', true);
                    $rows   = [];
                    foreach ($stored as $entry) {
                        $q2 = $db->getQuery(true)->select('*')->from('#__aaj_products')
                                 ->where('asin = ' . $db->quote($entry['asin'] ?? ''))->setLimit(1);
                        $db->setQuery($q2);
                        $r = $db->loadAssoc();
                        if ($r) $rows[] = $r;
                    }
                    if (!empty($rows)) return $rows;
                }
            }

            $key2   = $this->getParam('api_key', '');
            $secret = $this->getParam('api_secret', '');
            $tag    = $this->getParam('api_tag', '');
            if (!$key2 || !$secret || !$tag) return [];

            $api     = new \Aaj\Component\Aaj\Administrator\Api\AmazonApi($key2, $secret, $tag, $country);
            $apiType = $type === 'new_releases' ? 'NewReleases' : 'BestSellers';
            $items   = $api->getListItems($category, $apiType, $count);
            if (!$items) return [];

            $rows   = [];
            $stored = [];
            foreach ($items as $item) {
                $data = \Aaj\Component\Aaj\Administrator\Product\AajProduct::fromApiItem($item);
                if (!$data['asin']) continue;
                $pid      = $this->upsertProduct($db, $data);
                $stored[] = ['asin' => $data['asin'], 'id' => $pid];
                $rows[]   = $data;
            }
            $now = date('Y-m-d H:i:s');
            $this->upsertList($db, $key, $type, $category, $country, $stored, $now);
            return $rows;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function upsertProduct(\Joomla\Database\DatabaseInterface $db, array $data): int
    {
        $q = $db->getQuery(true)->select('id')->from('#__aaj_products')
                ->where('asin = ' . $db->quote($data['asin']))->setLimit(1);
        $db->setQuery($q);
        $existing = $db->loadResult();
        $data['date_updated'] = date('Y-m-d H:i:s');

        if ($existing) {
            $q2 = $db->getQuery(true)->update('#__aaj_products');
            foreach ($data as $col => $val) {
                if ($col !== 'id' && $col !== 'date_created') {
                    $q2->set($db->quoteName($col) . ' = ' . $db->quote($val));
                }
            }
            $q2->where('id = ' . (int)$existing);
            $db->setQuery($q2)->execute();
            return (int)$existing;
        }

        $data['date_created'] = $data['date_updated'];
        $q3 = $db->getQuery(true)->insert('#__aaj_products');
        foreach ($data as $col => $val) {
            $q3->columns($db->quoteName($col));
            $q3->values($db->quote($val));
        }
        $db->setQuery($q3)->execute();
        return (int)$db->insertid();
    }

    private function upsertList(\Joomla\Database\DatabaseInterface $db, string $key, string $type,
        string $category, string $country, array $stored, string $now): void
    {
        $q = $db->getQuery(true)->select('id')->from('#__aaj_lists')
                ->where('list_key = ' . $db->quote($key))->setLimit(1);
        $db->setQuery($q);
        $existing = $db->loadResult();
        $products = json_encode($stored);

        if ($existing) {
            $q2 = $db->getQuery(true)->update('#__aaj_lists')
                     ->set('products = ' . $db->quote($products))
                     ->set('date_updated = ' . $db->quote($now))
                     ->where('id = ' . (int)$existing);
            $db->setQuery($q2)->execute();
        } else {
            $q3 = $db->getQuery(true)->insert('#__aaj_lists')
                     ->columns(['type','list_key','country','category','products','date_updated'])
                     ->values(implode(',', [$db->quote($type),$db->quote($key),$db->quote($country),
                              $db->quote($category),$db->quote($products),$db->quote($now)]));
            $db->setQuery($q3)->execute();
        }
    }

    private function buildUrl(array $row, string $type = 'basic'): string
    {
        $asin = $row['asin'] ?? '';
        $tag  = $this->getParam('api_tag', '');
        $tld  = $this->getParam('api_country', 'com');
        switch ($type) {
            case 'cart':    return "https://www.amazon.{$tld}/gp/aws/cart/add.html?ASIN.1={$asin}&Quantity.1=1&AssociateTag={$tag}";
            case 'reviews': return "https://www.amazon.{$tld}/product-reviews/{$asin}?tag={$tag}";
            default:
                $url = $row['url'] ?? '';
                return $url ?: "https://www.amazon.{$tld}/dp/{$asin}?tag={$tag}";
        }
    }

    private function getParam(string $key, mixed $default = null): mixed
    {
        static $p = null;
        if ($p === null) {
            $p = \Joomla\CMS\Component\ComponentHelper::getParams('com_aaj');
        }
        return $p->get($key, $default);
    }

    private function enqueueAssets(): void
    {
        static $loaded = false;
        if ($loaded) return;
        $doc = Factory::getApplication()->getDocument();
        $doc->addStyleSheet(\Joomla\CMS\Uri\Uri::root() . 'media/com_aaj/css/main.css');
        $doc->addScript(\Joomla\CMS\Uri\Uri::root() . 'media/com_aaj/js/main.js');
        $loaded = true;
    }
}
