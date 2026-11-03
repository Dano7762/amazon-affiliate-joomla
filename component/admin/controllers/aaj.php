<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

class AajController extends BaseController
{
    protected $default_view = 'dashboard';

    public function display($cachable = false, $urlparams = [])
    {
        $view     = Factory::getApplication()->input->getCmd('view', $this->default_view);
        $viewPath = __DIR__ . '/../views/' . $view . '/view.html.php';

        if (!is_file($viewPath)) {
            $viewPath = __DIR__ . '/../views/dashboard/view.html.php';
            $view     = 'dashboard';
        }

        require_once $viewPath;

        $className = 'AajView' . ucfirst($view);
        $tmplPath  = dirname($viewPath) . '/tmpl';

        $viewObj = new $className([], ['template_path' => $tmplPath]);
        $viewObj->display();

        return $this;
    }

    public function asinpicker(): void
    {
        $viewPath = __DIR__ . '/../views/asinpicker/view.html.php';
        require_once $viewPath;
        $viewObj = new AajViewAsinpicker();
        $viewObj->display();
    }
}
