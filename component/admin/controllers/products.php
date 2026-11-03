<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;

class AajControllerProducts extends BaseController
{
    public function delete(): void
    {
        $this->checkToken();
        $ids = Factory::getApplication()->input->get('cid', [], 'array');
        $db  = Factory::getContainer()->get(DatabaseInterface::class);
        foreach (array_map('intval', $ids) as $id) {
            $db->setQuery($db->getQuery(true)->delete('#__aaj_products')->where('id='.$id))->execute();
        }
        $this->setRedirect(Route::_('index.php?option=com_aaj&view=products', false));
    }
    public function display($cachable = false, $urlparams = []) { return $this; }
}
