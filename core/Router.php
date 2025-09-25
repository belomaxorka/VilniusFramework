<?php

namespace Core;

class Router
{
    protected array $routes = [];

    public function get(string $uri, callable|array $action): void
    {
        $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, callable|array $action): void
    {
        $this->addRoute('POST', $uri, $action);
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

        $this->routes[$method][] = [
            'pattern' => $pattern,
            'action' => $action,
        ];
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
}
