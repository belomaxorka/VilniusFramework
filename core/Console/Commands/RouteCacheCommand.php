<?php declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;
use Core\Router;
use Core\Container;

/**
 * Route Cache Command
 * 
 * Создать кэш роутов для продакшена
 */
class RouteCacheCommand extends Command
{
    protected string $signature = 'route:cache';
    protected string $description = 'Create a route cache file for faster route loading';

    public function handle(): int
    {
        $this->info('Caching routes...');

        try {
            // Получаем роутер из контейнера
            $container = Container::getInstance();
            $router = $container->make(Router::class);
            
            // Включаем кэширование
            $router->enableCache();

            // Регистрируем middleware aliases
            $middlewareAliases = require ROOT . '/config/middleware.php';
            $router->registerMiddlewareAliases($middlewareAliases['aliases'] ?? []);

            // Загружаем роуты
            $routesFile = ROOT . '/routes/web.php';
            if (!file_exists($routesFile)) {
                $this->error('Routes file not found: routes/web.php');
                return 1;
            }

            require $routesFile;

            // Сохраняем в кэш
            if ($router->saveToCache()) {
                $routes = $router->getRoutes();
                $totalRoutes = 0;
                
                foreach ($routes as $methodRoutes) {
                    $totalRoutes += count($methodRoutes);
                }

                $this->newLine();
                $this->success('Routes cached successfully!');
                $this->line("  Total routes: {$totalRoutes}");
                $this->line("  Cache file: storage/cache/routes.php");
                
                $this->newLine();
                $this->info('Route cache will be used on next request.');
                
                return 0;
            } else {
                $this->error('Failed to cache routes.');
                return 1;
            }
        } catch (\Throwable $e) {
            $this->error('Failed to cache routes: ' . $e->getMessage());
            return 1;
        }
    }
}

