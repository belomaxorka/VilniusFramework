<?php declare(strict_types=1);

define('VILNIUS_START', microtime(true));

// Load bootstrap file
require_once __DIR__ . '/../core/bootstrap.php';

// Initialize app
\Core\Core::init();

// Initialize container
$container = \Core\Container::getInstance();

// Register services from config
$services = require __DIR__ . '/../config/services.php';

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

// Enable route caching in production
if (\Core\Environment::isProduction()) {
    $router->enableCache();
}

// Register middleware aliases
$middlewareConfig = require __DIR__ . '/../config/middleware.php';
$router->registerMiddlewareAliases($middlewareConfig['aliases'] ?? []);

// Load routes from cache (if available) or from routes file
$routesLoaded = false;

if (\Core\Environment::isProduction()) {
    $routesLoaded = $router->loadFromCache();
}

if (!$routesLoaded) {
    // Load routes from file
    $routesFile = __DIR__ . '/../routes/web.php';
    if (file_exists($routesFile)) {
        require $routesFile;
    } else {
        // Fallback to inline routes for backward compatibility
        $router->get('', [\App\Controllers\HomeController::class, 'index'])->name('home');
        $router->get('user/{name:[a-zA-Z]+}', [\App\Controllers\HomeController::class, 'name'])->name('user.profile');
    }
}

// Передаем Router в Debug Toolbar
\Core\DebugToolbar::setRouter($router);

// Let the Magic begin!
$router->dispatch(\Core\Http::getMethod(), \Core\Http::getUri());
