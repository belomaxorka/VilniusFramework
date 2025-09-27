<?php declare(strict_types=1);

// Define root path
define('ROOT', realpath(__DIR__ . '/../'));

// Load composer
require_once ROOT . '/vendor/autoload.php';

// Initialize app
\Core\App::init();

// Initialize router
$router = new \Core\Router();

// Routes
$router->get('', [\App\Controllers\HomeController::class, 'index']);
$router->get('about', fn() => print "About page");

// Run
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
