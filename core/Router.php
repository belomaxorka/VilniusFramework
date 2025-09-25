<?php

namespace Core;

class Router
{
    protected array $routes = [];

    public function get(string $uri, callable|array $action): void
    {
        $this->routes['GET'][$uri] = $action;
    }

    public function post(string $uri, callable|array $action): void
    {
        $this->routes['POST'][$uri] = $action;
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = trim(parse_url($uri, PHP_URL_PATH), '/');

        $action = $this->routes[$method][$uri] ?? null;

        if (!$action) {
            http_response_code(404);
            echo "404 Not Found";
            return;
        }

        if (is_array($action)) {
            [$controller, $method] = $action;
            if (!class_exists($controller)) {
                $controller = "App\\Controllers\\{$controller}";
            }
            (new $controller())->$method();
        }

    }
}
