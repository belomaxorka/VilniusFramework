<?php declare(strict_types=1);

namespace Core;

class Router
{
    protected array $routes = [];
    protected array $originalUris = [];
    protected array $namedRoutes = [];
    protected ?string $lastAddedRouteKey = null;
    protected array $groupStack = [];
    protected array $middlewareAliases = [];
    protected array $routeMiddleware = [];
    protected $notFoundHandler = null;
    protected bool $cacheEnabled = false;
    protected string $cachePath = '';
    protected ?Container $container = null;
    protected array $routeConstraints = [];
    protected ?Validation\RouteParameterValidator $validator = null;
    protected array $routeDomains = [];

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

    /**
     * Добавить middleware к последнему добавленному роуту
     */
    public function middleware(string|array $middleware): self
    {
        if ($this->lastAddedRouteKey === null) {
            throw new \LogicException('No route to assign middleware to. Call middleware() right after defining a route.');
        }

        $middleware = is_array($middleware) ? $middleware : [$middleware];

        if (!isset($this->routeMiddleware[$this->lastAddedRouteKey])) {
            $this->routeMiddleware[$this->lastAddedRouteKey] = [];
        }

        $this->routeMiddleware[$this->lastAddedRouteKey] = array_merge(
            $this->routeMiddleware[$this->lastAddedRouteKey],
            $middleware
        );

        return $this;
    }

    /**
     * Добавить ограничения валидации для параметров роута
     *
     * @param array $constraints ['param' => ['type' => 'int', 'rules' => ['min:1']]]
     * @return self
     */
    public function where(array $constraints): self
    {
        if ($this->lastAddedRouteKey === null) {
            throw new \LogicException('No route to assign constraints to. Call where() right after defining a route.');
        }

        $this->routeConstraints[$this->lastAddedRouteKey] = $constraints;

        return $this;
    }

    /**
     * Добавить ограничение для одного параметра
     *
     * @param string $param Имя параметра
     * @param array $constraint Ограничения
     * @return self
     */
    public function whereParam(string $param, array $constraint): self
    {
        if ($this->lastAddedRouteKey === null) {
            throw new \LogicException('No route to assign constraint to. Call whereParam() right after defining a route.');
        }

        if (!isset($this->routeConstraints[$this->lastAddedRouteKey])) {
            $this->routeConstraints[$this->lastAddedRouteKey] = [];
        }

        $this->routeConstraints[$this->lastAddedRouteKey][$param] = $constraint;

        return $this;
    }

    /**
     * Быстрые методы для типов параметров
     */
    public function whereNumber(string $param): self
    {
        return $this->whereParam($param, ['type' => 'int', 'rules' => ['numeric']]);
    }

    public function whereAlpha(string $param): self
    {
        return $this->whereParam($param, ['type' => 'string', 'rules' => ['alpha']]);
    }

    public function whereAlphaNumeric(string $param): self
    {
        return $this->whereParam($param, ['type' => 'string', 'rules' => ['alphanumeric']]);
    }

    public function whereUuid(string $param): self
    {
        return $this->whereParam($param, ['type' => 'string', 'rules' => ['uuid']]);
    }

    public function whereIn(string $param, array $values): self
    {
        return $this->whereParam($param, ['rules' => ['in:' . implode(',', $values)]]);
    }

    /**
     * Установить значение по умолчанию для опционального параметра
     *
     * @param string $param Имя параметра
     * @param mixed $default Значение по умолчанию
     * @return self
     */
    public function defaults(array $defaults): self
    {
        if ($this->lastAddedRouteKey === null) {
            throw new \LogicException('No route to assign defaults to. Call defaults() right after defining a route.');
        }

        if (!isset($this->routeConstraints[$this->lastAddedRouteKey])) {
            $this->routeConstraints[$this->lastAddedRouteKey] = [];
        }

        foreach ($defaults as $param => $value) {
            if (!isset($this->routeConstraints[$this->lastAddedRouteKey][$param])) {
                $this->routeConstraints[$this->lastAddedRouteKey][$param] = [];
            }
            
            $this->routeConstraints[$this->lastAddedRouteKey][$param]['default'] = $value;
            $this->routeConstraints[$this->lastAddedRouteKey][$param]['optional'] = true;
        }

        return $this;
    }

    protected function addRoute(string $method, string $uri, callable|array $action): void
    {
        // Применяем префикс группы, если есть
        $uri = $this->applyGroupPrefix($uri);

        // Обрабатываем опциональные параметры {param?} или {param:regex?}
        $hasOptionalParams = $this->hasOptionalParameters($uri);
        
        if ($hasOptionalParams) {
            // Генерируем несколько вариантов роута для опциональных параметров
            $this->addOptionalRoute($method, $uri, $action);
        } else {
            // Обычный роут без опциональных параметров
            $this->addSingleRoute($method, $uri, $action);
        }
    }

    /**
     * Добавить один роут без опциональных параметров
     */
    protected function addSingleRoute(string $method, string $uri, callable|array $action): void
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
        
        // Получаем домен из стека групп
        $domain = $this->getGroupDomain();
        
        $this->routes[$method][] = [
            'pattern' => $pattern,
            'action' => $action,
            'middleware' => $this->getGroupMiddleware(),
            'domain' => $domain,
        ];

        // Сохраняем оригинальный URI для отладки
        $this->originalUris[$method][$routeIndex] = $uri;

        // Сохраняем домен для этого роута
        if ($domain) {
            $this->routeDomains[$method . ':' . $routeIndex] = $domain;
        }

        // Сохраняем ключ последнего добавленного роута для name()
        $this->lastAddedRouteKey = $method . ':' . $routeIndex;
    }

    /**
     * Добавить роут с опциональными параметрами
     */
    protected function addOptionalRoute(string $method, string $uri, callable|array $action): void
    {
        // Разбиваем URI на сегменты
        $segments = explode('/', trim($uri, '/'));
        $patterns = [''];
        
        // Находим первый опциональный параметр
        $firstOptionalIndex = null;
        foreach ($segments as $index => $segment) {
            if (str_contains($segment, '?}')) {
                $firstOptionalIndex = $index;
                break;
            }
        }

        if ($firstOptionalIndex === null) {
            // Нет опциональных параметров, добавляем как обычный роут
            $this->addSingleRoute($method, $uri, $action);
            return;
        }

        // Создаем варианты роутов:
        // 1. Без опциональных параметров
        $requiredPart = implode('/', array_slice($segments, 0, $firstOptionalIndex));
        
        // 2. С каждым последующим опциональным параметром
        $currentPath = $requiredPart;
        $optionalSegments = array_slice($segments, $firstOptionalIndex);
        
        // Добавляем роут без опциональных параметров
        $this->addSingleRoute($method, $currentPath, $action);
        
        // Добавляем роуты с опциональными параметрами по одному
        foreach ($optionalSegments as $segment) {
            // Убираем знак вопроса из параметра
            $segment = str_replace('?}', '}', $segment);
            $currentPath .= '/' . $segment;
            $this->addSingleRoute($method, $currentPath, $action);
        }
    }

    /**
     * Проверить, есть ли в URI опциональные параметры
     */
    protected function hasOptionalParameters(string $uri): bool
    {
        return str_contains($uri, '?}');
    }

    /**
     * Применить префикс группы к URI
     */
    protected function applyGroupPrefix(string $uri): string
    {
        $prefix = '';
        
        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }

        return trim($prefix, '/') . '/' . trim($uri, '/');
    }

    /**
     * Получить middleware из текущих групп
     */
    protected function getGroupMiddleware(): array
    {
        $middleware = [];
        
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $middleware = array_merge($middleware, (array)$group['middleware']);
            }
        }

        return $middleware;
    }

    /**
     * Получить домен из текущих групп
     */
    protected function getGroupDomain(): ?string
    {
        // Берем домен из последней группы в стеке (более специфичный)
        for ($i = count($this->groupStack) - 1; $i >= 0; $i--) {
            if (isset($this->groupStack[$i]['domain'])) {
                return $this->groupStack[$i]['domain'];
            }
        }

        return null;
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = trim(parse_url($uri, PHP_URL_PATH), '/');
        $uri = preg_replace('#^index\.php/?#', '', $uri);

        // Получаем текущий домен
        $currentDomain = Http::getHost();

        foreach ($this->routes[$method] ?? [] as $index => $route) {
            // Проверяем домен, если он задан для роута
            if (isset($route['domain']) && !$this->matchesDomain($route['domain'], $currentDomain)) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Получаем ключ роута
                $routeKey = $method . ':' . $index;

                // Валидируем параметры, если есть ограничения
                if (isset($this->routeConstraints[$routeKey])) {
                    try {
                        $params = $this->validateParams($params, $this->routeConstraints[$routeKey]);
                    } catch (Validation\ValidationException $e) {
                        $this->handleValidationError($e);
                        return;
                    }
                }

                // Получаем middleware для этого роута
                $middleware = $this->routeMiddleware[$routeKey] ?? [];
                
                // Добавляем middleware из группы
                $middleware = array_merge($route['middleware'] ?? [], $middleware);

                // Создаем финальный обработчик
                $action = $route['action'];
                $finalHandler = function() use ($action, $params, $method) {
                    $result = null;
                    
                    if (is_array($action)) {
                        [$controller, $methodName] = $action;
                        if (!class_exists($controller)) {
                            $controller = "App\\Controllers\\{$controller}";
                        }
                        $result = $this->callControllerAction($controller, $methodName, $params);
                    } else {
                        $result = $action(...array_values($params));
                    }
                    
                    // Если возвращен Response объект, отправляем его
                    if ($result instanceof Response) {
                        $result->send();
                    }
                    
                    return $result;
                };

                // Выполняем middleware pipeline
                $this->runMiddlewarePipeline($middleware, $finalHandler);
                return;
            }
        }

        $this->handleNotFound($method, $uri);
    }

    /**
     * Обработать 404 ошибку
     */
    protected function handleNotFound(string $method, string $uri): void
    {
        http_response_code(404);

        // Если установлен кастомный обработчик
        if ($this->notFoundHandler !== null) {
            $handler = $this->notFoundHandler;
            
            if (is_array($handler)) {
                [$controller, $methodName] = $handler;
                if (!class_exists($controller)) {
                    $controller = "App\\Controllers\\{$controller}";
                }
                $this->callControllerAction($controller, $methodName, ['method' => $method, 'uri' => $uri]);
            } else {
                $handler($method, $uri);
            }
            return;
        }

        // Стандартная обработка 404
        $this->renderDefaultNotFound($method, $uri);
    }

    /**
     * Отрендерить стандартную страницу 404
     */
    protected function renderDefaultNotFound(string $method, string $uri): void
    {
        // Для JSON запросов
        if ($this->isJsonRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Not Found',
                'message' => "The requested resource was not found.",
                'path' => '/' . $uri,
                'method' => $method,
            ], JSON_PRETTY_PRINT);
            return;
        }

        // Для обычных запросов
        $isDebug = Environment::isDebug();
        
        echo $this->render404Page($method, $uri, $isDebug);
    }

    /**
     * Отрендерить HTML страницу 404
     */
    protected function render404Page(string $method, string $uri, bool $isDebug): string
    {
        $registeredRoutes = '';
        
        if ($isDebug) {
            $registeredRoutes = $this->renderRegisteredRoutes($method);
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 900px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .error-message {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .error-details {
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 40px;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .info-label {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-family: 'Courier New', monospace;
            background: white;
            padding: 12px;
            border-radius: 4px;
            font-size: 16px;
            color: #333;
        }
        .routes-section {
            margin-top: 30px;
        }
        .routes-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        .routes-table {
            width: 100%;
            border-collapse: collapse;
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
        }
        .routes-table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        .routes-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .routes-table tr:last-child td {
            border-bottom: none;
        }
        .routes-table tr:hover {
            background: white;
        }
        .method-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            color: white;
        }
        .method-GET { background: #4caf50; }
        .method-POST { background: #2196f3; }
        .method-PUT { background: #ff9800; }
        .method-PATCH { background: #9c27b0; }
        .method-DELETE { background: #f44336; }
        .actions {
            margin-top: 30px;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
            margin: 0 10px;
        }
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="error-code">404</div>
            <div class="error-message">Page Not Found</div>
            <div class="error-details">The requested resource could not be found</div>
        </div>
        
        <div class="content">
            <div class="info-box">
                <div class="info-label">Request Method</div>
                <div class="info-value">{$method}</div>
            </div>
            
            <div class="info-box">
                <div class="info-label">Request URI</div>
                <div class="info-value">/{$uri}</div>
            </div>

            {$registeredRoutes}
            
            <div class="actions">
                <a href="/" class="btn">Go to Homepage</a>
                <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Отрендерить зарегистрированные роуты для debug режима
     */
    protected function renderRegisteredRoutes(string $currentMethod): string
    {
        $html = '<div class="routes-section">';
        $html .= '<div class="routes-title">🛣️ Registered Routes for ' . $currentMethod . '</div>';
        
        if (empty($this->routes[$currentMethod])) {
            $html .= '<p style="color: #999; font-style: italic;">No routes registered for this method.</p>';
        } else {
            $html .= '<table class="routes-table">';
            $html .= '<thead><tr><th>Method</th><th>URI Pattern</th><th>Action</th></tr></thead>';
            $html .= '<tbody>';
            
            foreach ($this->routes[$currentMethod] as $index => $route) {
                $uri = htmlspecialchars($this->originalUris[$currentMethod][$index] ?? '');
                $action = $this->formatAction($route['action']);
                
                $html .= '<tr>';
                $html .= '<td><span class="method-badge method-' . $currentMethod . '">' . $currentMethod . '</span></td>';
                $html .= '<td>/' . $uri . '</td>';
                $html .= '<td>' . htmlspecialchars($action) . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody>';
            $html .= '</table>';
        }
        
        // Показать другие методы
        $otherMethods = array_keys($this->routes);
        $otherMethods = array_filter($otherMethods, fn($m) => $m !== $currentMethod);
        
        if (!empty($otherMethods)) {
            $html .= '<div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 6px; border-left: 4px solid #ffc107;">';
            $html .= '<strong>💡 Hint:</strong> There are routes registered for other HTTP methods: ';
            $html .= implode(', ', array_map(fn($m) => '<strong>' . $m . '</strong>', $otherMethods));
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Форматировать action для отображения
     */
    protected function formatAction($action): string
    {
        if (is_array($action)) {
            [$controller, $method] = $action;
            $shortController = is_string($controller) 
                ? (class_exists($controller) ? basename(str_replace('\\', '/', $controller)) : $controller)
                : 'Closure';
            return $shortController . '::' . $method;
        }
        
        return 'Closure';
    }

    /**
     * Проверить, является ли запрос JSON
     */
    protected function isJsonRequest(): bool
    {
        return Http::isJson() || Http::acceptsJson();
    }

    /**
     * Выполнить цепочку middleware
     */
    protected function runMiddlewarePipeline(array $middleware, callable $finalHandler): void
    {
        // Создаем pipeline из middleware
        $pipeline = array_reduce(
            array_reverse($middleware),
            function ($next, $middlewareName) {
                return function () use ($middlewareName, $next) {
                    $middleware = $this->resolveMiddleware($middlewareName);
                    return $middleware->handle($next);
                };
            },
            $finalHandler
        );

        // Выполняем pipeline
        $pipeline();
    }

    /**
     * Создать экземпляр middleware
     */
    protected function resolveMiddleware(string $name): Middleware\MiddlewareInterface
    {
        // Проверяем алиасы
        if (isset($this->middlewareAliases[$name])) {
            $class = $this->middlewareAliases[$name];
        } elseif (class_exists($name)) {
            $class = $name;
        } else {
            throw new \InvalidArgumentException("Middleware '{$name}' not found.");
        }

        return new $class();
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

    /**
     * Создать группу роутов
     *
     * @param array{prefix?: string, middleware?: string|array<string>, domain?: string} $attributes Атрибуты группы
     * @param callable $callback Коллбэк для регистрации роутов внутри группы
     * @return void
     */
    public function group(array $attributes, callable $callback): void
    {
        // Добавляем группу в стек
        $this->groupStack[] = $attributes;

        // Выполняем коллбэк
        $callback($this);

        // Удаляем группу из стека
        array_pop($this->groupStack);
    }

    /**
     * Создать группу роутов для определенного домена/поддомена
     *
     * @param string $domain Домен (например: 'api.example.com', '{subdomain}.example.com')
     * @param callable $callback
     * @return void
     */
    public function domain(string $domain, callable $callback): void
    {
        $this->group(['domain' => $domain], $callback);
    }

    /**
     * Проверить, соответствует ли текущий домен паттерну
     */
    protected function matchesDomain(string $pattern, string $domain): bool
    {
        // Если паттерн точно совпадает с доменом
        if ($pattern === $domain) {
            return true;
        }

        // Преобразуем паттерн домена в regex
        // {subdomain}.example.com -> (?P<subdomain>[^.]+)\.example\.com
        $regex = preg_replace_callback(
            '#\{(\w+)(?::([^}]+))?\}#',
            function ($matches) {
                $name = $matches[1];
                $regex = $matches[2] ?? '[^.]+'; // По умолчанию - любые символы кроме точки
                return '(?P<' . $name . '>' . $regex . ')';
            },
            $pattern
        );

        // Экранируем точки в домене
        $regex = str_replace('.', '\.', $regex);
        $regex = '#^' . $regex . '$#i';

        return preg_match($regex, $domain) === 1;
    }

    /**
     * Зарегистрировать ресурсный контроллер
     *
     * Создает стандартные RESTful роуты для CRUD операций:
     * - GET    /resource           -> index   (список всех ресурсов)
     * - GET    /resource/create    -> create  (форма создания)
     * - POST   /resource           -> store   (сохранить новый)
     * - GET    /resource/{id}      -> show    (показать один)
     * - GET    /resource/{id}/edit -> edit    (форма редактирования)
     * - PUT    /resource/{id}      -> update  (обновить)
     * - DELETE /resource/{id}      -> destroy (удалить)
     *
     * @param string $uri Базовый URI ресурса
     * @param string $controller Класс контроллера
     * @param array $options Опции: ['only' => [], 'except' => [], 'names' => [], 'middleware' => []]
     * @return void
     */
    public function resource(string $uri, string $controller, array $options = []): void
    {
        $only = $options['only'] ?? [];
        $except = $options['except'] ?? [];
        $names = $options['names'] ?? [];
        $middleware = $options['middleware'] ?? [];
        $parameter = $options['parameter'] ?? 'id';

        // Определяем базовое имя для роутов
        $baseName = $options['as'] ?? str_replace('/', '.', trim($uri, '/'));

        // Все доступные действия
        $actions = [
            'index' => ['GET', $uri, 'index'],
            'create' => ['GET', $uri . '/create', 'create'],
            'store' => ['POST', $uri, 'store'],
            'show' => ['GET', $uri . '/{' . $parameter . ':\d+}', 'show'],
            'edit' => ['GET', $uri . '/{' . $parameter . ':\d+}/edit', 'edit'],
            'update' => ['PUT', $uri . '/{' . $parameter . ':\d+}', 'update'],
            'destroy' => ['DELETE', $uri . '/{' . $parameter . ':\d+}', 'destroy'],
        ];

        foreach ($actions as $action => [$method, $actionUri, $controllerMethod]) {
            // Пропускаем если в except
            if (!empty($except) && in_array($action, $except)) {
                continue;
            }

            // Пропускаем если указан only и этого действия нет в списке
            if (!empty($only) && !in_array($action, $only)) {
                continue;
            }

            // Создаем роут
            $route = $this->addRouteByMethod($method, $actionUri, [$controller, $controllerMethod]);

            // Добавляем имя роута
            $routeName = $names[$action] ?? ($baseName . '.' . $action);
            $this->name($routeName);

            // Добавляем middleware
            if (!empty($middleware)) {
                $this->middleware($middleware);
            }

            // Добавляем валидацию для ID параметра
            if (in_array($action, ['show', 'edit', 'update', 'destroy'])) {
                $this->whereNumber($parameter);
            }
        }
    }

    /**
     * Зарегистрировать API ресурсный контроллер (без create и edit)
     *
     * @param string $uri
     * @param string $controller
     * @param array $options
     * @return void
     */
    public function apiResource(string $uri, string $controller, array $options = []): void
    {
        $options['except'] = array_merge($options['except'] ?? [], ['create', 'edit']);
        $this->resource($uri, $controller, $options);
    }

    /**
     * Добавить роут по методу HTTP
     */
    protected function addRouteByMethod(string $method, string $uri, callable|array $action): self
    {
        return match(strtoupper($method)) {
            'GET' => $this->get($uri, $action),
            'POST' => $this->post($uri, $action),
            'PUT' => $this->put($uri, $action),
            'PATCH' => $this->patch($uri, $action),
            'DELETE' => $this->delete($uri, $action),
            'OPTIONS' => $this->options($uri, $action),
            'HEAD' => $this->head($uri, $action),
            default => throw new \InvalidArgumentException("Invalid HTTP method: {$method}"),
        };
    }

    /**
     * Зарегистрировать алиас для middleware
     */
    public function aliasMiddleware(string $alias, string $class): void
    {
        $this->middlewareAliases[$alias] = $class;
    }

    /**
     * Зарегистрировать несколько алиасов для middleware
     */
    public function registerMiddlewareAliases(array $aliases): void
    {
        foreach ($aliases as $alias => $class) {
            $this->aliasMiddleware($alias, $class);
        }
    }

    /**
     * Установить кастомный обработчик 404 ошибки
     *
     * @param callable|array $handler Обработчик (closure или [Controller::class, 'method'])
     * @return void
     */
    public function setNotFoundHandler(callable|array $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    /**
     * Установить контейнер зависимостей
     *
     * @param Container $container
     * @return void
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * Получить контейнер зависимостей
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        if ($this->container === null) {
            $this->container = Container::getInstance();
        }

        return $this->container;
    }

    /**
     * Вызвать action контроллера с внедрением зависимостей
     *
     * @param string $controller
     * @param string $method
     * @param array $params
     * @return mixed
     */
    protected function callControllerAction(string $controller, string $method, array $params): mixed
    {
        $container = $this->getContainer();

        // Создаем контроллер через контейнер (с DI в конструкторе)
        $instance = $container->make($controller);

        // Вызываем метод с параметрами роута
        return $instance->$method(...array_values($params));
    }

    /**
     * Валидировать параметры роута
     */
    protected function validateParams(array $params, array $constraints): array
    {
        if ($this->validator === null) {
            $this->validator = new Validation\RouteParameterValidator();
        }

        return $this->validator->validate($params, $constraints);
    }

    /**
     * Обработать ошибку валидации
     */
    protected function handleValidationError(Validation\ValidationException $e): void
    {
        http_response_code(422); // Unprocessable Entity

        if ($this->isJsonRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Validation Failed',
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ], JSON_PRETTY_PRINT);
        } else {
            echo $this->renderValidationErrorPage($e);
        }
    }

    /**
     * Отрендерить HTML страницу ошибки валидации
     */
    protected function renderValidationErrorPage(Validation\ValidationException $e): string
    {
        $errors = $e->getErrors();
        $errorList = '';
        
        foreach ($errors as $error) {
            $errorList .= '<li>' . htmlspecialchars($error) . '</li>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>422 - Validation Failed</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .error-code {
            font-size: 80px;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 10px;
        }
        .error-message {
            font-size: 24px;
        }
        .content {
            padding: 40px;
        }
        h2 {
            color: #f5576c;
            margin-bottom: 15px;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        li {
            background: #fff0f0;
            border-left: 4px solid #f5576c;
            padding: 12px 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            color: #c92a2a;
        }
        .actions {
            margin-top: 30px;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #f5576c;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="error-code">422</div>
            <div class="error-message">Validation Failed</div>
        </div>
        <div class="content">
            <h2>The following validation errors occurred:</h2>
            <ul>{$errorList}</ul>
            <div class="actions">
                <a href="javascript:history.back()" class="btn">Go Back</a>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Включить кеширование роутов
     *
     * @param string $cachePath Путь к файлу кеша
     * @return void
     */
    public function enableCache(string $cachePath = ''): void
    {
        $this->cacheEnabled = true;
        $this->cachePath = $cachePath ?: __DIR__ . '/../storage/cache/routes.php';
    }

    /**
     * Отключить кеширование роутов
     *
     * @return void
     */
    public function disableCache(): void
    {
        $this->cacheEnabled = false;
    }

    /**
     * Загрузить роуты из кеша
     *
     * @return bool True если кеш загружен, false если кеш недоступен
     */
    public function loadFromCache(): bool
    {
        if (!$this->cacheEnabled || !file_exists($this->cachePath)) {
            return false;
        }

        $cached = require $this->cachePath;

        if (!is_array($cached) || !isset($cached['routes'], $cached['originalUris'], $cached['namedRoutes'])) {
            return false;
        }

        $this->routes = $cached['routes'];
        $this->originalUris = $cached['originalUris'];
        $this->namedRoutes = $cached['namedRoutes'];
        $this->routeMiddleware = $cached['routeMiddleware'] ?? [];

        return true;
    }

    /**
     * Сохранить роуты в кеш
     *
     * @return bool True если успешно сохранено, false при ошибке
     */
    public function saveToCache(): bool
    {
        if (!$this->cacheEnabled) {
            return false;
        }

        $cacheDir = dirname($this->cachePath);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $data = [
            'routes' => $this->routes,
            'originalUris' => $this->originalUris,
            'namedRoutes' => $this->namedRoutes,
            'routeMiddleware' => $this->routeMiddleware,
            'cached_at' => date('Y-m-d H:i:s'),
        ];

        $export = $this->varExportFormatted($data);
        $content = "<?php\n\n// Routes cache generated at " . date('Y-m-d H:i:s') . "\n// Do not edit this file manually.\n\nreturn " . $export . ";\n";

        return file_put_contents($this->cachePath, $content, LOCK_EX) !== false;
    }

    /**
     * Очистить кеш роутов
     *
     * @return bool True если кеш удален или не существовал
     */
    public function clearCache(): bool
    {
        if (!$this->cacheEnabled || !file_exists($this->cachePath)) {
            return true;
        }

        return unlink($this->cachePath);
    }

    /**
     * Проверить, существует ли кеш
     *
     * @return bool
     */
    public function isCached(): bool
    {
        return $this->cacheEnabled && file_exists($this->cachePath);
    }

    /**
     * Форматированный var_export для красивого кода
     */
    protected function varExportFormatted($var, int $indent = 0): string
    {
        if (is_array($var)) {
            $indexed = array_keys($var) === range(0, count($var) - 1);
            $r = [];
            $spaces = str_repeat('    ', $indent);
            
            foreach ($var as $key => $value) {
                $r[] = $spaces . '    '
                    . ($indexed ? '' : var_export($key, true) . ' => ')
                    . $this->varExportFormatted($value, $indent + 1);
            }
            
            return "[\n" . implode(",\n", $r) . "\n" . $spaces . ']';
        }
        
        if (is_string($var)) {
            return var_export($var, true);
        }
        
        if (is_bool($var)) {
            return $var ? 'true' : 'false';
        }
        
        if (is_null($var)) {
            return 'null';
        }
        
        return var_export($var, true);
    }
}
