<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

class AajViewLists
{
    public array $lists = [];
    public int   $total = 0;

    public function __construct(array $config = [], array $paths = []) {}

    public function display($tpl = null): void
    {
        ToolbarHelper::title('AAJ - Lists', 'list');
        ToolbarHelper::deleteList('', 'lists.delete');

        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $db->setQuery($db->getQuery(true)->select('*')->from('#__aaj_lists')->order('date_updated DESC'));
            $this->lists = $db->loadAssocList() ?: [];
            $db->setQuery($db->getQuery(true)->select('COUNT(*)')->from('#__aaj_lists'));
            $this->total = (int) $db->loadResult();
        } catch (\Exception $e) {}

        $tmpl = __DIR__ . '/tmpl/default.php';
        if (is_file($tmpl)) include $tmpl;
    }
}
