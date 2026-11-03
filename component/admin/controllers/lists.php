<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;

class AajControllerLists extends BaseController
{
    public function delete(): void
    {
        $this->checkToken();
        $ids = Factory::getApplication()->input->get('cid', [], 'array');
        $db  = Factory::getContainer()->get(DatabaseInterface::class);
        foreach (array_map('intval', $ids) as $id) {
            $db->setQuery($db->getQuery(true)->delete('#__aaj_lists')->where('id='.$id))->execute();
        }
        $this->setRedirect(Route::_('index.php?option=com_aaj&view=lists', false));
    }
    public function purgeAll(): void
    {
        $this->checkToken();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery($db->getQuery(true)->delete('#__aaj_lists'))->execute();
        $this->setRedirect(Route::_('index.php?option=com_aaj&view=lists', false));
    }
    public function display($cachable = false, $urlparams = []) { return $this; }
}
