<?php declare(strict_types=1);

// Define paths
define('ROOT', realpath(__DIR__ . '/../'));
define('CONFIG_DIR', ROOT . '/config');
define('LANG_DIR', ROOT . '/lang');
define('STORAGE_DIR', ROOT . '/storage');
define('CACHE_DIR', STORAGE_DIR . '/cache');
define('LOG_DIR', STORAGE_DIR . '/logs');

// Load composer
require_once ROOT . '/vendor/autoload.php';

// Initialize app
\Core\Core::init();

// Initialize router
$router = new \Core\Router();

// Routes
$router->get('', [\App\Controllers\HomeController::class, 'index']);
$router->get('user/{name:[a-zA-Z]+}', [\App\Controllers\HomeController::class, 'name']);
$router->get('debug', [\App\Controllers\HomeController::class, 'debug']);

Debug::dump(['test' => 'data'], 'Test');

// Let the Magic begin!
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
