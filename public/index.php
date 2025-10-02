<?php declare(strict_types=1);

use Core\Config;
use Core\Container;
use Core\Core;
use Core\Environment;
use Core\Http;

define('VILNIUS_START', microtime(true));

// Load bootstrap file
require_once __DIR__ . '/../core/bootstrap.php';

// Initialize app
Core::init();

// Initialize container
$container = Container::getInstance();

// Register services from config
$services = Config::get('services');
foreach ($services['singletons'] ?? [] as $abstract => $concrete) {
    $container->singleton($abstract, $concrete);
}
foreach ($services['bindings'] ?? [] as $abstract => $concrete) {
    $container->bind($abstract, $concrete);
}
foreach ($services['aliases'] ?? [] as $alias => $abstract) {
    $container->alias($alias, $abstract);
}

// Initialize router
$router = $container->make(\Core\Router::class);
if (Environment::isProduction()) {
    $router->enableCache();
}

// Register middleware
$middlewareConfig = Config::get('middleware');
$router->registerMiddlewareAliases($middlewareConfig['aliases'] ?? []);
$router->registerGlobalMiddleware($middlewareConfig['global'] ?? []);

// Load routes from cache (if available) or from routes file
$routesLoaded = false;
if (Environment::isProduction()) {
    $routesLoaded = $router->loadFromCache();
}

if (!$routesLoaded) {
    // Load routes from file
    $routesFile = ROOT . '/routes/web.php';
    if (!is_file($routesFile)) {
        throw new RuntimeException("Routes file not found: {$routesFile}");
    }
    require_once $routesFile;
}

// Let the Magic begin!
\Core\DebugToolbar::setRouter($router);
$router->dispatch(Http::getMethod(), Http::getUri());
