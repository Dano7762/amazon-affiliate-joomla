<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

class AajViewProducts
{
    public array $products = [];
    public int   $total    = 0;

    public function __construct(array $config = [], array $paths = []) {}

    public function display($tpl = null): void
    {
        ToolbarHelper::title('AAJ - Products', 'list');
        ToolbarHelper::deleteList('', 'products.delete');

        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $db->setQuery($db->getQuery(true)->select('*')->from('#__aaj_products')
                ->order('date_updated DESC')->setLimit(50));
            $this->products = $db->loadAssocList() ?: [];
            $db->setQuery($db->getQuery(true)->select('COUNT(*)')->from('#__aaj_products'));
            $this->total = (int) $db->loadResult();
        } catch (\Exception $e) {}

        $tmpl = __DIR__ . '/tmpl/default.php';
        if (is_file($tmpl)) include $tmpl;
    }
}
