<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

// Autoloader for AAJ classes
$classPath = __DIR__ . '/classes';
spl_autoload_register(function (string $class) use ($classPath): void {
    $prefix = 'Aaj\\Component\\Aaj\\Administrator\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) return;
    $rel  = substr($class, strlen($prefix));
    $file = $classPath . DIRECTORY_SEPARATOR
          . str_replace('\\', DIRECTORY_SEPARATOR, $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// Load all legacy controllers
foreach (glob(__DIR__ . '/controllers/*.php') as $f) {
    require_once $f;
}

$app   = Factory::getApplication();
$input = $app->input;
$task  = $input->getCmd('task', '');
$view  = $input->getCmd('view', 'dashboard');

// Route task to controller
if (strpos($task, '.') !== false) {
    [$ctrlName, $method] = explode('.', $task, 2);
} else {
    $ctrlName = $task ?: 'display';
    $method   = 'display';
}

$controllerMap = [
    'display'  => 'AajController',
    'settings' => 'AajControllerSettings',
    'products' => 'AajControllerProducts',
    'lists'    => 'AajControllerLists',
    'api'      => 'AajControllerApi',
];

$className = $controllerMap[strtolower($ctrlName)] ?? 'AajController';

if (!class_exists($className)) {
    require_once __DIR__ . '/controllers/aaj.php';
    $className = 'AajController';
    $method    = 'display';
}

$controller = new $className();
$controller->execute($method);
$controller->redirect();
