<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;

class AajViewAsinpicker
{
    public string $editorName    = '';
    public string $token         = '';
    public string $root          = '';
    public bool   $apiConfigured = false;

    public function __construct(array $config = [], array $paths = []) {}

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();

        $this->editorName = $app->input->getString('editor', '');
        $this->token      = Session::getFormToken();
        $this->root       = Uri::root();

        $params = ComponentHelper::getParams('com_aaj');
        $this->apiConfigured = !empty($params->get('api_key', ''))
                            && !empty($params->get('api_secret', ''))
                            && !empty($params->get('api_tag', ''));

        $tmpl = __DIR__ . '/tmpl/default.php';
        if (is_file($tmpl)) {
            include $tmpl;
        }
    }
}
