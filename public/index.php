<?php declare(strict_types=1);

define('VILNIUS_START', microtime(true));

// Load bootstrap file
require_once __DIR__ . '/../core/bootstrap.php';

// Initialize app
\Core\Core::init();

// Initialize router
$router = new \Core\Router();

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
