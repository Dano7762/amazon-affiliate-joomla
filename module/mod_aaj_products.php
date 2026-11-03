<?php
defined('_JEXEC') or die;
use Joomla\CMS\Helper\ModuleHelper;

// Same autoloader as plugin
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

require ModuleHelper::getLayoutPath('mod_aaj_products', $params->get('template', 'horizontal'));
