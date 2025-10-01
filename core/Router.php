<?php declare(strict_types=1);

namespace Core;

class Router
{
    protected array $routes = [];
    protected array $originalUris = [];
    protected array $namedRoutes = [];
    protected ?string $lastAddedRouteKey = null;

    public function get(string $uri, callable|array $action): self
    {
        $this->addRoute('GET', $uri, $action);
        return $this;
    }

    public function post(string $uri, callable|array $action): self
    {
        $this->addRoute('POST', $uri, $action);
        return $this;
    }

    public function put(string $uri, callable|array $action): self
    {
        $this->addRoute('PUT', $uri, $action);
        return $this;
    }

    public function patch(string $uri, callable|array $action): self
    {
        $this->addRoute('PATCH', $uri, $action);
        return $this;
    }

    public function delete(string $uri, callable|array $action): self
    {
        $this->addRoute('DELETE', $uri, $action);
        return $this;
    }

    public function options(string $uri, callable|array $action): self
    {
        $this->addRoute('OPTIONS', $uri, $action);
        return $this;
    }

    public function head(string $uri, callable|array $action): self
    {
        $this->addRoute('HEAD', $uri, $action);
        return $this;
    }

    public function any(string $uri, callable|array $action): self
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'] as $method) {
            $this->addRoute($method, $uri, $action);
        }
        return $this;
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

        // Сохраняем ключ последнего добавленного роута для name()
        $this->lastAddedRouteKey = $method . ':' . $routeIndex;
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
     * Присвоить имя последнему добавленному роуту
     */
    public function name(string $name): self
    {
        if ($this->lastAddedRouteKey === null) {
            throw new \LogicException('No route to assign name to. Call name() right after defining a route.');
        }

        if (isset($this->namedRoutes[$name])) {
            throw new \LogicException("Route name '{$name}' is already in use.");
        }

        $this->namedRoutes[$name] = $this->lastAddedRouteKey;

        return $this;
    }

    /**
     * Сгенерировать URL по имени роута
     *
     * @param string $name Имя роута
     * @param array<string, mixed> $params Параметры для подстановки
     * @return string
     * @throws \InvalidArgumentException
     */
    public function route(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \InvalidArgumentException("Route '{$name}' not found.");
        }

        [$method, $index] = explode(':', $this->namedRoutes[$name]);
        $uri = $this->originalUris[$method][(int)$index] ?? '';

        if (empty($uri)) {
            throw new \InvalidArgumentException("URI for route '{$name}' not found.");
        }

        // Заменяем параметры в URI
        $url = preg_replace_callback(
            '#\{(\w+)(?::([^}]+))?\}#',
            function ($matches) use ($params, $name) {
                $paramName = $matches[1];
                
                if (!array_key_exists($paramName, $params)) {
                    throw new \InvalidArgumentException(
                        "Missing required parameter '{$paramName}' for route '{$name}'."
                    );
                }

                return (string)$params[$paramName];
            },
            $uri
        );

        return '/' . trim($url, '/');
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

    /**
     * Получить все именованные роуты
     *
     * @return array<string, string>
     */
    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }
}
