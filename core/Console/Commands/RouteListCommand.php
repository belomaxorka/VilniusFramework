<?php declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;
use Core\Router;
use Core\Container;

/**
 * Route List Command
 * 
 * Показать список всех зарегистрированных роутов
 */
class RouteListCommand extends Command
{
    protected string $signature = 'route:list';
    protected string $description = 'List all registered routes';

    public function handle(): int
    {
        $this->info('Registered Routes:');
        $this->newLine();

        // Получаем роутер из контейнера
        $container = Container::getInstance();
        $router = $container->make(Router::class);

        // Загружаем роуты
        $routesFile = ROOT . '/routes/web.php';
        if (file_exists($routesFile)) {
            require_once $routesFile;
        }

        $routes = $router->getRoutes();

        if (empty($routes)) {
            $this->warning('No routes registered.');
            return 0;
        }

        $rows = [];
        $totalRoutes = 0;

        foreach ($routes as $method => $methodRoutes) {
            foreach ($methodRoutes as $route) {
                $uri = $route['uri'] ?? '/';
                $action = $this->formatAction($route['action']);
                
                $rows[] = [
                    $method,
                    '/' . trim($uri, '/'),
                    $action,
                ];
                
                $totalRoutes++;
            }
        }

        // Сортируем по URI
        usort($rows, fn($a, $b) => strcmp($a[1], $b[1]));

        $this->table(
            ['Method', 'URI', 'Action'],
            $rows
        );

        $this->newLine();
        $this->info("Total routes: {$totalRoutes}");

        return 0;
    }

    /**
     * Форматировать action для отображения
     */
    private function formatAction(mixed $action): string
    {
        if (is_array($action)) {
            [$controller, $method] = $action;
            $shortController = $this->getShortClassName($controller);
            return "{$shortController}@{$method}";
        }

        if ($action instanceof \Closure) {
            return 'Closure';
        }

        return 'Unknown';
    }

    /**
     * Получить короткое имя класса
     */
    private function getShortClassName(string $class): string
    {
        $parts = explode('\\', $class);
        return end($parts);
    }
}

