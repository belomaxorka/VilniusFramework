<?php declare(strict_types=1);

define('VILNIUS_START', microtime(true));

// Load bootstrap file
require_once __DIR__ . '/../core/bootstrap.php';

// Initialize app
\Core\Core::init();

// Initialize router
$router = new \Core\Router();

// Routes
$router->get('', [\App\Controllers\HomeController::class, 'index']);
$router->get('user/{name:[a-zA-Z]+}', [\App\Controllers\HomeController::class, 'name']);
$router->any('demo', [\App\Controllers\RequestDemoController::class, 'demo']);

// Передаем Router в Debug Toolbar
\Core\DebugToolbar::setRouter($router);

// Let the Magic begin!
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
