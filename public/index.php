<?php

define('ROOT', realpath(__DIR__ . '/../'));

require_once ROOT . '/vendor/autoload.php';

\Core\App::init();

$router = new \Core\Router();

// --------- Routes ---------
$router->get('', [\App\Controllers\HomeController::class, 'index']);
$router->get('about', fn() => print "About page");

// Run
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
