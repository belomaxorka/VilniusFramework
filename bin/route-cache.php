#!/usr/bin/env php
<?php declare(strict_types=1);

/**
 * Route Cache Management CLI
 * 
 * Usage:
 *   php bin/route-cache.php cache    - Create routes cache
 *   php bin/route-cache.php clear    - Clear routes cache
 *   php bin/route-cache.php status   - Check cache status
 */

// Load bootstrap
require_once __DIR__ . '/../core/bootstrap.php';

// Initialize app
\Core\Core::init();

// Get command
$command = $argv[1] ?? 'help';

// Create router instance
$router = new \Core\Router();
$router->enableCache();

// Register middleware aliases
$middlewareAliases = require __DIR__ . '/../config/middleware.php';
$router->registerMiddlewareAliases($middlewareAliases['aliases'] ?? []);

// Load routes from your routes file
// You should move route definitions to a separate file, e.g., routes/web.php
$routesFile = __DIR__ . '/../routes/web.php';
if (!file_exists($routesFile)) {
    // Create example routes file
    echo "⚠️  Routes file not found. Creating example routes file...\n";
    
    $routesDir = dirname($routesFile);
    if (!is_dir($routesDir)) {
        mkdir($routesDir, 0755, true);
    }
    
    $exampleRoutes = <<<'PHP'
<?php declare(strict_types=1);

/**
 * Web Routes
 * 
 * Register your application routes here.
 */

// Example routes
$router->get('', [\App\Controllers\HomeController::class, 'index'])->name('home');
$router->get('user/{name:[a-zA-Z]+}', [\App\Controllers\HomeController::class, 'name'])->name('user.profile');

// Add your routes here...

PHP;
    
    file_put_contents($routesFile, $exampleRoutes);
    echo "✅ Created: routes/web.php\n\n";
}

// Load routes
require $routesFile;

// Execute command
switch ($command) {
    case 'cache':
        echo "🔄 Caching routes...\n";
        
        if ($router->saveToCache()) {
            $totalRoutes = array_sum(array_map('count', $router->getRoutes()));
            echo "✅ Routes cached successfully!\n";
            echo "📊 Total routes: {$totalRoutes}\n";
            echo "📁 Cache file: storage/cache/routes.php\n";
        } else {
            echo "❌ Failed to cache routes.\n";
            exit(1);
        }
        break;
        
    case 'clear':
        echo "🔄 Clearing route cache...\n";
        
        if ($router->clearCache()) {
            echo "✅ Route cache cleared successfully!\n";
        } else {
            echo "⚠️  Cache file not found or already cleared.\n";
        }
        break;
        
    case 'status':
        if ($router->isCached()) {
            $cachePath = __DIR__ . '/../storage/cache/routes.php';
            $cacheTime = filemtime($cachePath);
            $cacheAge = time() - $cacheTime;
            
            echo "📦 Route cache status:\n";
            echo "   Status: ✅ Cached\n";
            echo "   File: storage/cache/routes.php\n";
            echo "   Created: " . date('Y-m-d H:i:s', $cacheTime) . "\n";
            echo "   Age: " . formatSeconds($cacheAge) . "\n";
            echo "   Size: " . formatBytes(filesize($cachePath)) . "\n";
        } else {
            echo "📦 Route cache status:\n";
            echo "   Status: ❌ Not cached\n";
            echo "   Run 'php bin/route-cache.php cache' to create cache.\n";
        }
        break;
        
    case 'help':
    default:
        echo <<<HELP

Route Cache Management
======================

Available commands:

  cache    Create routes cache for production
  clear    Clear the cached routes
  status   Show cache status
  help     Show this help message

Usage:
  php bin/route-cache.php [command]

Examples:
  php bin/route-cache.php cache     # Cache routes for faster loading
  php bin/route-cache.php clear     # Clear route cache
  php bin/route-cache.php status    # Check if routes are cached

HELP;
        break;
}

// Helper functions
function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

function formatSeconds(int $seconds): string
{
    if ($seconds < 60) {
        return $seconds . ' seconds';
    } elseif ($seconds < 3600) {
        return floor($seconds / 60) . ' minutes';
    } elseif ($seconds < 86400) {
        return floor($seconds / 3600) . ' hours';
    } else {
        return floor($seconds / 86400) . ' days';
    }
}

