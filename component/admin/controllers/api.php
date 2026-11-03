<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Component\ComponentHelper;

class AajControllerApi extends BaseController
{
    public function search(): void
    {
        $app = Factory::getApplication();

        // CSRF check
        if (!Session::checkToken('get')) {
            $this->sendJson(false, 'Token invalide.', []);
        }

        $keywords = trim($app->input->getString('keywords', ''));
        if (!$keywords) {
            $this->sendJson(false, 'Mots-clés manquants.', []);
        }

        // Load params
        $params = ComponentHelper::getParams('com_aaj');
        $key     = $params->get('api_key', '');
        $secret  = $params->get('api_secret', '');
        $tag     = $params->get('api_tag', '');
        $country = $params->get('api_country', 'fr');

        if (!$key || !$secret || !$tag) {
            $this->sendJson(false, 'Les clés API ne sont pas configurées dans AAJ → Settings.', [], true);
        }

        // Register autoloader
        $compSrc = JPATH_ADMINISTRATOR . '/components/com_aaj/classes';
        spl_autoload_register(function (string $class) use ($compSrc): void {
            $prefix = 'Aaj\\Component\\Aaj\\Administrator\\';
            if (strncmp($class, $prefix, strlen($prefix)) !== 0) return;
            $file = $compSrc . DIRECTORY_SEPARATOR
                  . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) require_once $file;
        });

        try {
            $api   = \Aaj\Component\Aaj\Administrator\Api\AmazonApi::create($key, $secret, $tag, $country);
            $items = $api->searchItems($keywords, 'All', 10);

            if ($items === null) {
                $errors = $api->getErrors();
                $first  = $errors[0] ?? [];
                $code   = $first['Code'] ?? 0;
                $msg    = $first['Message'] ?? 'Erreur API inconnue.';

                if ($code === 403) {
                    $this->sendJson(false,
                        'Accès API non encore activé par Amazon (3 ventes requises). '
                        . 'Utilisez l\'onglet "Saisie manuelle" pour entrer l\'ASIN directement.',
                        [], true);
                }

                $this->sendJson(false, 'Erreur API (' . $code . ') : ' . $msg, []);
            }

            $results = [];
            foreach ($items as $item) {
                $data = \Aaj\Component\Aaj\Administrator\Product\AajProduct::fromApiItem($item);
                if (empty($data['asin'])) continue;

                $imgs  = json_decode($data['image_ids'] ?? '{}', true);
                $image = $imgs['small'] ?? $imgs['medium'] ?? '';

                $price = '';
                if (!empty($data['price']) && !empty($data['currency'])) {
                    $symbols = [
                        'EUR' => '€', 'USD' => '$', 'GBP' => '£', 'JPY' => '¥',
                        'CAD' => 'CA$', 'AUD' => 'A$',
                    ];
                    $symbol = $symbols[$data['currency']] ?? $data['currency'] . ' ';
                    $price  = $symbol . number_format((float)$data['price'], 2, ',', '.');
                }

                $results[] = [
                    'asin'  => $data['asin'],
                    'title' => $data['title'],
                    'image' => $image,
                    'price' => $price,
                ];
            }

            if (empty($results)) {
                $this->sendJson(false, 'Aucun résultat trouvé pour "' . htmlspecialchars($keywords) . '".', []);
            }

            $this->sendJson(true, '', $results);

        } catch (\Exception $e) {
            $this->sendJson(false, 'Exception : ' . $e->getMessage(), []);
        }
    }

    private function sendJson(bool $success, string $message, array $data, bool $apiUnavailable = false): void
    {
        Factory::getApplication()->setHeader('Content-Type', 'application/json; charset=utf-8');
        echo json_encode([
            'success'         => $success,
            'message'         => $message,
            'data'            => $data,
            'api_unavailable' => $apiUnavailable,
        ]);
        Factory::getApplication()->close();
    }
}
