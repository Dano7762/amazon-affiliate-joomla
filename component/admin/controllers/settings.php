<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;

class AajControllerSettings extends BaseController
{
    public function save(): void
    {
        $this->checkToken();
        $app  = Factory::getApplication();
        $data = $app->input->get('jform', [], 'array');
        $db   = Factory::getContainer()->get(DatabaseInterface::class);

        // Load current params
        $q = $db->getQuery(true)
            ->select('params')
            ->from('#__extensions')
            ->where('element = ' . $db->quote('com_aaj'))
            ->where('type = ' . $db->quote('component'));
        $db->setQuery($q);
        $raw    = $db->loadResult();
        $params = new \Joomla\Registry\Registry($raw ?: '{}');

        $fields = [
            'api_key', 'api_secret', 'api_tag', 'api_country',
            'shortcode_tag', 'disclaimer', 'button_style',
            'box_template', 'bestseller_template', 'new_releases_template',
        ];
        foreach ($fields as $f) {
            if (isset($data[$f])) {
                $params->set($f, $data[$f]);
            }
        }
        foreach (['cache_duration', 'title_length'] as $f) {
            if (isset($data[$f])) {
                $params->set($f, (int) $data[$f]);
            }
        }

        $u = $db->getQuery(true)
            ->update('#__extensions')
            ->set('params = ' . $db->quote($params->toString('JSON')))
            ->where('element = ' . $db->quote('com_aaj'))
            ->where('type = ' . $db->quote('component'));
        $db->setQuery($u)->execute();

        $app->enqueueMessage('Settings saved successfully.');
        $this->setRedirect(Route::_('index.php?option=com_aaj&view=settings', false));
    }

    public function testApi(): void
    {
        $this->checkToken('request');
        $app     = Factory::getApplication();
        $key     = trim($app->input->getString('api_key', ''));
        $secret  = trim($app->input->getString('api_secret', ''));
        $tag     = trim($app->input->getString('api_tag', ''));
        $country = trim($app->input->getString('api_country', 'fr'));

        if (!$key || !$secret || !$tag) {
            echo json_encode(['success' => false, 'message' => 'Missing credentials']);
            $app->close();
            return;
        }

        // Load the API class
        $apiFile = JPATH_ADMINISTRATOR . '/components/com_aaj/classes/Api/AmazonApi.php';
        if (!is_file($apiFile)) {
            echo json_encode(['success' => false, 'message' => 'API class not found: ' . $apiFile]);
            $app->close();
            return;
        }
        require_once $apiFile;

        $api = \Aaj\Component\Aaj\Administrator\Api\AmazonApi::create($key, $secret, $tag, $country);

        if ($api->testCredentials()) {
            // Save verified credentials to component params
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $q = $db->getQuery(true)
                ->select('params')
                ->from('#__extensions')
                ->where('element = ' . $db->quote('com_aaj'))
                ->where('type = ' . $db->quote('component'));
            $db->setQuery($q);
            $raw = $db->loadResult();

            $params = new \Joomla\Registry\Registry($raw ?: '{}');
            $params->set('api_key',     $key);
            $params->set('api_secret',  $secret);
            $params->set('api_tag',     $tag);
            $params->set('api_country', $country);
            $params->set('api_status',  1);

            $u = $db->getQuery(true)
                ->update('#__extensions')
                ->set('params = ' . $db->quote($params->toString('JSON')))
                ->where('element = ' . $db->quote('com_aaj'))
                ->where('type = ' . $db->quote('component'));
            $db->setQuery($u)->execute();

            echo json_encode(['success' => true, 'message' => 'Connection successful! Credentials saved.']);
        } else {
            $errors = $api->getErrors();
            $msg    = !empty($errors[0]['Message']) ? $errors[0]['Message'] : 'Connection failed';
            echo json_encode(['success' => false, 'message' => $msg]);
        }

        $app->close();
    }

    public function display($cachable = false, $urlparams = [])
    {
        return $this;
    }
}
