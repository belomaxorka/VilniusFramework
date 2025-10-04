<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
use Core\DebugToolbar\ColorPalette;
use Core\Router;

/**
 * Коллектор маршрутов
 */
class RoutesCollector extends AbstractCollector
{
    private ?Router $router = null;
    private ?string $currentMethod = null;
    private ?string $currentUri = null;

    public function __construct()
    {
        $this->priority = 85;
    }

    public function getName(): string
    {
        return 'routes';
    }

    public function getTitle(): string
    {
        return 'Routes';
    }

    public function getIcon(): string
    {
        return '🛣️';
    }

    /**
     * Установить Router для анализа
     */
    public function setRouter(Router $router): void
    {
        $this->router = $router;
    }

    /**
     * Установить текущий запрос
     */
    public function setCurrentRequest(string $method, string $uri): void
    {
        $this->currentMethod = $method;
        $this->currentUri = trim(parse_url($uri, PHP_URL_PATH) ?? '', '/');
    }

    public function collect(): void
    {
        if (!$this->router) {
            $this->data = [
                'routes' => [],
                'total' => 0,
                'current' => null,
            ];
            return;
        }

        $routes = $this->extractRoutes();
        $current = $this->findCurrentRoute($routes);

        $this->data = [
            'routes' => $routes,
            'total' => count($routes),
            'current' => $current,
        ];
    }

    public function render(): string
    {
        if (empty($this->data['routes'])) {
            return '<div style="padding: 20px; color: #757575; font-style: italic;">No routes registered</div>';
        }

        $html = '<div style="padding: 20px;">';

        // Current route
        if ($this->data['current']) {
            $html .= '<div style="background: #e8f5e9; border-left: 4px solid #4caf50; padding: 15px; margin-bottom: 20px; border-radius: 4px;">';
            $html .= '<h3 style="margin: 0 0 10px 0; color: #2e7d32;">✅ Current Route</h3>';
            $html .= '<div style="font-family: monospace; font-size: 14px;">';
            $html .= '<strong style="color: #1976d2;">' . htmlspecialchars($this->data['current']['method']) . '</strong> ';
            $html .= '<span style="color: #388e3c;">' . htmlspecialchars($this->data['current']['uri']) . '</span> → ';
            $html .= '<span style="color: #f57c00;">' . htmlspecialchars($this->data['current']['action']) . '</span>';
            $html .= '</div>';
            $html .= '</div>';
        }

        // All routes
        $html .= '<h3>All Routes (' . $this->data['total'] . ')</h3>';

        // Group by method
        $grouped = [];
        foreach ($this->data['routes'] as $route) {
            $grouped[$route['method']][] = $route;
        }

        // Render table
        $html .= '<table style="width: 100%; border-collapse: collapse; background: white;">';
        $html .= '<thead>';
        $html .= '<tr style="background: #1976d2; color: white;">';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Method</th>';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">URI Pattern</th>';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Action</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($grouped as $method => $routes) {
            foreach ($routes as $route) {
                $isCurrent = $this->data['current']
                    && $route['method'] === $this->data['current']['method']
                    && $route['uri'] === $this->data['current']['uri'];

                $rowStyle = $isCurrent ? 'background: #e8f5e9;' : '';

                $html .= '<tr style="' . $rowStyle . '">';

                // Method
                $methodColor = $this->getMethodColor($method);
                $html .= '<td style="padding: 8px; border: 1px solid #ddd;">';
                $html .= '<span style="background: ' . $methodColor . '; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;">';
                $html .= htmlspecialchars($method);
                $html .= '</span>';
                if ($isCurrent) {
                    $html .= ' <span style="color: #4caf50;">✓</span>';
                }
                $html .= '</td>';

                // URI
                $html .= '<td style="padding: 8px; border: 1px solid #ddd; font-family: monospace;">';
                $html .= htmlspecialchars($route['uri']);
                $html .= '</td>';

                // Action
                $html .= '<td style="padding: 8px; border: 1px solid #ddd; font-family: monospace; font-size: 13px;">';
                $html .= htmlspecialchars($route['action']);
                $html .= '</td>';

                $html .= '</tr>';
            }
        }

        $html .= '</tbody>';
        $html .= '</table>';

        // Stats
        $html .= '<div style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px;">';
        $html .= '<strong>Statistics:</strong> ';

        $methodCounts = [];
        foreach ($this->data['routes'] as $route) {
            $methodCounts[$route['method']] = ($methodCounts[$route['method']] ?? 0) + 1;
        }

        $stats = [];
        foreach ($methodCounts as $method => $count) {
            $color = $this->getMethodColor($method);
            $stats[] = '<span style="color: ' . $color . '; font-weight: bold;">' . $method . '</span>: ' . $count;
        }

        $html .= implode(' | ', $stats);
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    public function getBadge(): ?string
    {
        return (string)$this->data['total'];
    }

    public function getHeaderStats(): array
    {
        if ($this->data['current']) {
            return [
                [
                    'icon' => '🛣️',
                    'value' => $this->data['current']['method'] . ' ' . $this->data['current']['uri'],
                    'color' => ColorPalette::SECONDARY,
                ],
            ];
        }

        return [];
    }

    /**
     * Извлечь маршруты из Router
     */
    private function extractRoutes(): array
    {
        $routes = [];

        // Используем публичный метод getRoutes()
        $routesData = $this->router->getRoutes();

        foreach ($routesData as $method => $methodRoutes) {
            foreach ($methodRoutes as $route) {
                $routes[] = [
                    'method' => $method,
                    'uri' => $route['uri'] ?: $this->patternToUri($route['pattern']),
                    'pattern' => $route['pattern'],
                    'action' => $this->actionToString($route['action']),
                ];
            }
        }

        return $routes;
    }

    /**
     * Найти текущий маршрут
     */
    private function findCurrentRoute(array $routes): ?array
    {
        if (!$this->currentMethod || !$this->currentUri) {
            return null;
        }

        foreach ($routes as $route) {
            if ($route['method'] === $this->currentMethod) {
                // Проверяем совпадение с паттерном
                if (preg_match($route['pattern'], $this->currentUri)) {
                    return $route;
                }
            }
        }

        return null;
    }

    /**
     * Преобразовать regex паттерн в читаемый URI
     */
    private function patternToUri(string $pattern): string
    {
        // #^user/(?P<name>[a-zA-Z]+)$# → /user/{name}
        $uri = preg_replace('#^\^#', '', $pattern);
        $uri = preg_replace('#\$$#', '', $uri);
        $uri = preg_replace('#\(\?P<(\w+)>[^)]+\)#', '{$1}', $uri);
        $uri = '/' . $uri;

        return $uri;
    }

    /**
     * Преобразовать action в строку
     */
    private function actionToString(mixed $action): string
    {
        if (is_array($action)) {
            [$controller, $method] = $action;

            // Убираем полный namespace для краткости
            $shortController = is_string($controller)
                ? (class_exists($controller) ? basename(str_replace('\\', '/', $controller)) : $controller)
                : 'Closure';

            return $shortController . '::' . $method;
        }

        if (is_callable($action)) {
            return 'Closure';
        }

        return 'Unknown';
    }

}

