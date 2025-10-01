# HTTP Класс

Утилитный класс для работы с HTTP-запросами.

## Содержание

- [Введение](#введение)
- [Основные методы](#основные-методы)
- [Информация о запросе](#информация-о-запросе)
- [Работа с заголовками](#работа-с-заголовками)
- [Проверка типов запросов](#проверка-типов-запросов)
- [Получение данных](#получение-данных)
- [Примеры использования](#примеры-использования)

## Введение

Класс `Core\Http` предоставляет удобный интерфейс для работы с HTTP-запросами, инкапсулируя прямой доступ к суперглобальным массивам PHP (`$_SERVER`, `$_GET`, `$_POST` и т.д.).

### Преимущества использования

- **Безопасность**: Все методы проверяют наличие данных перед доступом
- **Удобство**: Простой и понятный API
- **Тестируемость**: Легко мокировать для тестирования
- **Согласованность**: Единый способ работы с HTTP во всем приложении

## Основные методы

### Получение информации о запросе

```php
use Core\Http;

// Метод запроса (GET, POST, PUT, etc.)
$method = Http::getMethod(); // "GET"

// URI запроса
$uri = Http::getUri(); // "/users/123?sort=name"

// Путь без query string
$path = Http::getPath(); // "/users/123"

// Query string
$query = Http::getQueryString(); // "sort=name"

// Протокол
$protocol = Http::getProtocol(); // "HTTP/1.1"

// Схема (http или https)
$scheme = Http::getScheme(); // "https"

// Хост
$host = Http::getHost(); // "example.com"

// Порт
$port = Http::getPort(); // 443
```

### URL методы

```php
// Полный URL текущего запроса
$fullUrl = Http::getFullUrl(); 
// "https://example.com:8080/users/123?sort=name"

// Базовый URL (без пути)
$baseUrl = Http::getBaseUrl();
// "https://example.com:8080"
```

### Информация о клиенте

```php
// IP-адрес клиента (с поддержкой прокси)
$ip = Http::getClientIp(); // "192.168.1.1"

// User Agent
$userAgent = Http::getUserAgent();
// "Mozilla/5.0 (Windows NT 10.0; Win64; x64)..."

// Referer
$referer = Http::getReferer(); // "https://google.com"

// Время начала запроса
$time = Http::getRequestTime(); // 1234567890.1234
```

## Работа с заголовками

```php
// Получить все заголовки
$headers = Http::getHeaders();
/*
[
    'Accept' => 'text/html',
    'User-Agent' => 'Mozilla/5.0...',
    'Authorization' => 'Bearer token123',
]
*/

// Получить конкретный заголовок
$auth = Http::getHeader('Authorization'); // "Bearer token123"
$accept = Http::getHeader('accept'); // Регистронезависимо

// Получить принимаемые типы контента
$types = Http::getAcceptedContentTypes();
// ['text/html', 'application/json', '*/*']
```

## Проверка типов запросов

### Проверка схемы

```php
// Проверить HTTPS
if (Http::isSecure()) {
    // Безопасное соединение
}
```

### Проверка методов

```php
// Универсальная проверка
if (Http::isMethod('POST')) {
    // POST запрос
}

// Специализированные методы
if (Http::isGet()) {
    // GET запрос
}

if (Http::isPost()) {
    // POST запрос
}

if (Http::isPut()) {
    // PUT запрос
}

if (Http::isPatch()) {
    // PATCH запрос
}

if (Http::isDelete()) {
    // DELETE запрос
}
```

### Проверка типов контента

```php
// AJAX запрос
if (Http::isAjax()) {
    // Запрос от JavaScript
}

// JSON запрос (по Content-Type)
if (Http::isJson()) {
    // Content-Type: application/json
}

// Проверка Accept header
if (Http::acceptsJson()) {
    // Клиент принимает JSON
    return json_response(['status' => 'ok']);
}

if (Http::acceptsHtml()) {
    // Клиент принимает HTML
    return view('page');
}
```

## Получение данных

### GET параметры

```php
// Все GET параметры
$params = Http::getQueryParams();
// ['id' => '123', 'sort' => 'name']
```

### POST данные

```php
// Все POST данные
$data = Http::getPostData();
// ['username' => 'john', 'email' => 'john@example.com']
```

### JSON данные

```php
// Получить и декодировать JSON из php://input
$data = Http::getJsonData(); // Массив по умолчанию
// ['name' => 'John', 'age' => 30]

$data = Http::getJsonData(false); // Объект
// stdClass {name: "John", age: 30}

// Получить сырые данные из input
$raw = Http::getInputData();
// '{"name":"John","age":30}'
```

### Файлы

```php
// Все загруженные файлы
$files = Http::getFiles();
// $_FILES массив
```

### Куки

```php
// Все куки
$cookies = Http::getCookies();
// ['session_id' => 'abc123', 'user_lang' => 'ru']

// Конкретная кука
$session = Http::getCookie('session_id'); // "abc123"
```

## Примеры использования

### Пример 1: API endpoint с проверкой типов

```php
use Core\Http;

function apiEndpoint()
{
    // Проверяем метод
    if (!Http::isPost()) {
        return json_response(['error' => 'Method not allowed'], 405);
    }

    // Проверяем Content-Type
    if (!Http::isJson()) {
        return json_response(['error' => 'JSON expected'], 400);
    }

    // Получаем данные
    $data = Http::getJsonData();

    // Обрабатываем...
    return json_response(['status' => 'success']);
}
```

### Пример 2: Определение ответа по Accept header

```php
use Core\Http;

function handleRequest()
{
    $data = ['users' => [...], 'total' => 100];

    if (Http::acceptsJson()) {
        return json_response($data);
    }

    if (Http::acceptsHtml()) {
        return view('users/list', $data);
    }

    return response('Not Acceptable', 406);
}
```

### Пример 3: AJAX обработчик

```php
use Core\Http;

function loadMore()
{
    if (!Http::isAjax()) {
        return redirect('/');
    }

    $page = Http::getQueryParams()['page'] ?? 1;
    $items = loadItems($page);

    return json_response($items);
}
```

### Пример 4: Логирование запросов

```php
use Core\Http;
use Core\Logger;

function logRequest()
{
    Logger::info('HTTP Request', [
        'method' => Http::getMethod(),
        'url' => Http::getFullUrl(),
        'ip' => Http::getClientIp(),
        'user_agent' => Http::getUserAgent(),
        'referer' => Http::getReferer(),
        'is_ajax' => Http::isAjax(),
        'is_secure' => Http::isSecure(),
    ]);
}
```

### Пример 5: Защита от direct access

```php
use Core\Http;

function protectedAction()
{
    // Проверяем, что запрос пришел с нашего сайта
    $referer = Http::getReferer();
    $host = Http::getHost();

    if (empty($referer) || !str_contains($referer, $host)) {
        return response('Access Denied', 403);
    }

    // Продолжаем обработку...
}
```

### Пример 6: Content Negotiation

```php
use Core\Http;

function contentNegotiation($data)
{
    $acceptTypes = Http::getAcceptedContentTypes();

    foreach ($acceptTypes as $type) {
        switch ($type) {
            case 'application/json':
                return json_response($data);

            case 'application/xml':
                return xml_response($data);

            case 'text/html':
                return view('data', ['data' => $data]);

            case '*/*':
                // Дефолтный ответ
                return json_response($data);
        }
    }

    return response('Not Acceptable', 406);
}
```

### Пример 7: Rate limiting по IP

```php
use Core\Http;
use Core\Cache;

function checkRateLimit()
{
    $ip = Http::getClientIp();
    $key = "rate_limit:$ip";

    $requests = Cache::get($key, 0);

    if ($requests >= 100) {
        return response('Too Many Requests', 429);
    }

    Cache::set($key, $requests + 1, 60); // 100 запросов в минуту
}
```

### Пример 8: Проверка безопасности

```php
use Core\Http;

function checkSecurity()
{
    // Требуем HTTPS в production
    if (is_production() && !Http::isSecure()) {
        return redirect('https://' . Http::getHost() . Http::getUri());
    }

    // Проверяем наличие CSRF токена для POST запросов
    if (Http::isPost()) {
        $token = Http::getPostData()['csrf_token'] ?? '';
        if (!validateCsrfToken($token)) {
            return response('CSRF Token Mismatch', 419);
        }
    }
}
```

## Интеграция с другими компонентами

### Router

```php
use Core\Http;
use Core\Router;

Router::add('POST', '/api/users', function() {
    // Http класс автоматически доступен
    $data = Http::getJsonData();
    // ...
});
```

### Debug Toolbar

Класс `Http` активно используется в `RequestCollector` для сбора информации о запросе:

```php
// core/DebugToolbar/Collectors/RequestCollector.php
public function collect(): void
{
    $this->data = [
        'method' => Http::getMethod(),
        'uri' => Http::getUri(),
        'scheme' => Http::getScheme(),
        'host' => Http::getHost(),
        // ...
    ];
}
```

### Logger

```php
use Core\Http;
use Core\Logger;

// Логирование с контекстом запроса
Logger::info('User action', [
    'ip' => Http::getClientIp(),
    'url' => Http::getFullUrl(),
    'method' => Http::getMethod(),
]);
```

## Лучшие практики

1. **Используйте Http класс вместо прямого доступа к $_SERVER**
   ```php
   // ❌ Плохо
   $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
   
   // ✅ Хорошо
   $method = Http::getMethod();
   ```

2. **Используйте специализированные методы для проверок**
   ```php
   // ❌ Плохо
   if ($_SERVER['REQUEST_METHOD'] === 'POST') { }
   
   // ✅ Хорошо
   if (Http::isPost()) { }
   ```

3. **Проверяйте типы запросов перед обработкой**
   ```php
   if (Http::isPost() && Http::isJson()) {
       $data = Http::getJsonData();
       // ...
   }
   ```

4. **Используйте методы для безопасного доступа к данным**
   ```php
   // ✅ Хорошо - всегда возвращает значение
   $ip = Http::getClientIp();
   $method = Http::getMethod();
   ```

## Тестирование

Для тестирования кода, использующего `Http` класс, вы можете мокировать суперглобальные массивы:

```php
test('handles POST request', function () {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    
    expect(Http::isPost())->toBeTrue();
    expect(Http::isJson())->toBeTrue();
});
```

## См. также

- [Router](Router.md) - Маршрутизация запросов
- [Debug Toolbar](DebugToolbar.md) - Отладочная панель
- [Logger](Logger.md) - Логирование

