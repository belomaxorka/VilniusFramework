<?php declare(strict_types=1);

namespace Core;

class Router
{
    protected array $routes = [];
    protected array $originalUris = [];
    protected array $namedRoutes = [];
    protected ?string $lastAddedRouteKey = null;

    public function get(string $uri, callable|array $action): void
    {
        $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, callable|array $action): void
    {
        $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, callable|array $action): void
    {
        $this->addRoute('PUT', $uri, $action);
    }

    public function patch(string $uri, callable|array $action): void
    {
        $this->addRoute('PATCH', $uri, $action);
    }

    public function delete(string $uri, callable|array $action): void
    {
        $this->addRoute('DELETE', $uri, $action);
    }

    public function options(string $uri, callable|array $action): void
    {
        $this->addRoute('OPTIONS', $uri, $action);
    }

    public function head(string $uri, callable|array $action): void
    {
        $this->addRoute('HEAD', $uri, $action);
    }

    public function any(string $uri, callable|array $action): void
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'] as $method) {
            $this->addRoute($method, $uri, $action);
        }
    }

    protected function addRoute(string $method, string $uri, callable|array $action): void
    {
        // Преобразуем {param:regex} в (?P<param>regex)
        $pattern = preg_replace_callback(
            '#\{(\w+)(?::([^}]+))?\}#',
            function ($matches) {
                $name = $matches[1];
                $regex = $matches[2] ?? '[^/]+';
                return '(?P<' . $name . '>' . $regex . ')';
            },
            $uri
        );

        $pattern = '#^' . trim($pattern, '/') . '$#';

        $routeIndex = count($this->routes[$method] ?? []);
        
        $this->routes[$method][] = [
            'pattern' => $pattern,
            'action' => $action,
        ];

        // Сохраняем оригинальный URI для отладки
        $this->originalUris[$method][$routeIndex] = $uri;
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = trim(parse_url($uri, PHP_URL_PATH), '/');
        $uri = preg_replace('#^index\.php/?#', '', $uri);

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                $action = $route['action'];

                if (is_array($action)) {
                    [$controller, $method] = $action;
                    if (!class_exists($controller)) {
                        $controller = "App\\Controllers\\{$controller}";
                    }
                    (new $controller())->$method(...array_values($params));
                } else {
                    $action(...array_values($params));
                }
                return;
            }
        }

        http_response_code(404);
        echo "404 Not Found: [$uri]";
    }

    /**
     * Получить все зарегистрированные роуты
     *
     * @return array<string, array<int, array{uri: string, pattern: string, action: callable|array}>>
     */
    public function getRoutes(): array
    {
        $routes = [];

        foreach ($this->routes as $method => $methodRoutes) {
            foreach ($methodRoutes as $index => $route) {
                $routes[$method][] = [
                    'uri' => $this->originalUris[$method][$index] ?? '',
                    'pattern' => $route['pattern'],
                    'action' => $route['action'],
                ];
            }
        }

        return $routes;
    }
}
