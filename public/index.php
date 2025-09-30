<?php declare(strict_types=1);

define('VILNIUS_START', microtime(true));

// Define paths
define('ROOT', realpath(__DIR__ . '/../'));
define('CONFIG_DIR', ROOT . '/config');
define('LANG_DIR', ROOT . '/lang');
define('STORAGE_DIR', ROOT . '/storage');
define('CACHE_DIR', STORAGE_DIR . '/cache');
define('LOG_DIR', STORAGE_DIR . '/logs');

// Load composer
require_once ROOT . '/vendor/autoload.php';

// Early error handling initialization
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', LOG_DIR . '/php_errors.log');

// Initialize app
\Core\Core::init();

// Initialize router
$router = new \Core\Router();

// Routes
$router->get('', [\App\Controllers\HomeController::class, 'index']);
$router->get('user/{name:[a-zA-Z]+}', [\App\Controllers\HomeController::class, 'name']);

// Let the Magic begin!
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
