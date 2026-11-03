<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Component\ComponentHelper;

class AajViewDashboard
{
    public int  $totalProducts = 0;
    public int  $totalLists    = 0;
    public bool $apiReady      = false;

    public function __construct(array $config = [], array $paths = []) {}

    public function display($tpl = null): void
    {
        ToolbarHelper::title('AAJ - Dashboard', 'home');

        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $db->setQuery($db->getQuery(true)->select('COUNT(*)')->from('#__aaj_products'));
            $this->totalProducts = (int) $db->loadResult();
            $db->setQuery($db->getQuery(true)->select('COUNT(*)')->from('#__aaj_lists'));
            $this->totalLists = (int) $db->loadResult();
        } catch (\Exception $e) {}

        $this->apiReady = (bool) ComponentHelper::getParams('com_aaj')->get('api_status', false);

        $tmpl = __DIR__ . '/tmpl/default.php';
        if (is_file($tmpl)) {
            include $tmpl;
        }
    }
}
