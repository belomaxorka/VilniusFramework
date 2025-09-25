<?php

use Core\App;
use Core\Router;

require_once __DIR__ . '/../vendor/autoload.php';

App::init();

$router = new Router();

// Пример маршрутов
$router->get('', [\App\Controllers\HomeController::class, 'index']);
$router->get('about', fn() => print "About page");

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
