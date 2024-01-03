<?php

require dirname(__DIR__, 3) . '/bootstrap.php';

//make sure error reporting is on for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Install a fresh database.
file_put_contents('php://stdout', "Dropping test database schema...\n");
\Omeka\Test\DbTestCase::dropSchema();
file_put_contents('php://stdout', "Creating test database schema...\n");
\Omeka\Test\DbTestCase::installSchema();

// Login as admin
$application = \Omeka\Test\DbTestCase::getApplication();
$serviceLocator = $application->getServiceManager();
$auth = $serviceLocator->get('Omeka\AuthenticationService');
$adapter = $auth->getAdapter();
$adapter->setIdentity('admin@example.com');
$adapter->setCredential('root');
$auth->authenticate();

$moduleName = 'Search';

// Enable Search module
$moduleManager = $serviceLocator->get('Omeka\ModuleManager');
$module = $moduleManager->getModule($moduleName);
if ($module->getState() !== \Omeka\Module\Manager::STATE_ACTIVE) {
    $moduleManager->install($module);
}

spl_autoload_register(function ($class) use ($moduleName) {
    $prefix = "$moduleName\\Test\\";
    $base_dir = __DIR__ . "/$moduleName/";

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});
