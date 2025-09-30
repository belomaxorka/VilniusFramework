# Response Collector

Response Collector - это коллектор для Debug Toolbar, который отображает информацию о HTTP ответе сервера.

## Возможности

### 1. HTTP Status Code
- Отображает код ответа (200, 404, 500, etc.)
- Текстовое описание статуса
- Цветовая кодировка по категориям
- HTTP протокол (HTTP/1.1, HTTP/2)

### 2. Quick Stats
- ⏱️ **Response Time** - время генерации ответа
- 📄 **Content-Type** - тип контента
- 📦 **Content-Length** - размер ответа
- 📋 **Headers Count** - количество заголовков

### 3. Response Headers
Полный список отправленных HTTP заголовков в табличном виде.

### 4. Status Code Information
Подробное описание категории статус-кода.

## Цветовая кодировка статусов

| Категория | Цвет | Примеры |
|-----------|------|---------|
| 1xx Informational | 🔵 Синий | 100 Continue, 101 Switching Protocols |
| 2xx Success | 🟢 Зеленый | 200 OK, 201 Created, 204 No Content |
| 3xx Redirection | 🟠 Оранжевый | 301 Moved, 302 Found, 304 Not Modified |
| 4xx Client Error | 🔴 Красно-оранжевый | 400 Bad Request, 404 Not Found |
| 5xx Server Error | 🔴 Красный | 500 Internal Error, 503 Service Unavailable |

## Использование

Response Collector автоматически собирает данные при рендеринге Debug Toolbar.

### Автоматический сбор

```php
// Ничего не нужно делать - работает автоматически!
// Response Collector сам определит:
// - Status code (через http_response_code())
// - Headers (через headers_list())
// - Response time (через APP_START/VILNIUS_START)
```

### Ручная установка данных (опционально)

Если нужно установить данные вручную:

```php
$responseCollector = \Core\DebugToolbar::getCollector('response');

$responseCollector->setResponseData(
    statusCode: 200,
    headers: ['Content-Type' => 'application/json'],
    contentLength: 1024,
    contentType: 'application/json',
    responseTime: 45.5
);
```

## Примеры

### Успешный ответ (200 OK)

```
HTTP Response Status
200 OK
HTTP/1.1

⏱️ Response Time: 45.3 ms
📄 Content-Type: text/html; charset=UTF-8
📦 Content-Length: 15.4 KB
📋 Headers: 12 sent

Response Headers:
┌──────────────────┬───────────────────────────┐
│ Header           │ Value                     │
├──────────────────┼───────────────────────────┤
│ Content-Type     │ text/html; charset=UTF-8  │
│ X-Powered-By     │ TorrentPier               │
│ Cache-Control    │ no-cache                  │
└──────────────────┴───────────────────────────┘

Status Code Information:
✅ Success - The request was successfully received, 
understood, and accepted.
```

### Ошибка 404

```
HTTP Response Status
404 Not Found
HTTP/1.1

Status Code Information:
❌ Client Error - The request contains bad syntax 
or cannot be fulfilled.
```

### Ошибка 500

```
HTTP Response Status
500 Internal Server Error
HTTP/1.1

Status Code Information:
🔥 Server Error - The server failed to fulfill 
an apparently valid request.
```

## Response Time индикация

Response Collector использует цветовую кодировку времени:

- 🟢 **< 100ms** - Fast (зеленый)
- 🟠 **100-500ms** - Medium (оранжевый)  
- 🔴 **> 500ms** - Slow (красный)

## Header Toolbar

Response Collector добавляет информацию в header toolbar:

```
📤 200 OK
```

С цветовой кодировкой в зависимости от статуса.

## Badge

В заголовке вкладки Response отображается status code:

```
📤 Response [200]
```

## Приоритет

Response Collector имеет приоритет **88**, отображается после Request Collector (90) и перед Routes Collector (85).

## Поддерживаемые статус-коды

Response Collector знает все стандартные HTTP статус-коды:

### 1xx Informational
- 100 Continue
- 101 Switching Protocols
- 102 Processing
- 103 Early Hints

### 2xx Success
- 200 OK
- 201 Created
- 202 Accepted
- 204 No Content
- 206 Partial Content
- ... и другие

### 3xx Redirection
- 301 Moved Permanently
- 302 Found
- 303 See Other
- 304 Not Modified
- 307 Temporary Redirect
- 308 Permanent Redirect

### 4xx Client Errors
- 400 Bad Request
- 401 Unauthorized
- 403 Forbidden
- 404 Not Found
- 405 Method Not Allowed
- 422 Unprocessable Entity
- 429 Too Many Requests
- ... и другие

### 5xx Server Errors
- 500 Internal Server Error
- 502 Bad Gateway
- 503 Service Unavailable
- 504 Gateway Timeout
- ... и другие

## API

### Методы

```php
// Получить коллектор
$collector = \Core\DebugToolbar::getCollector('response');

// Установить данные вручную
$collector->setResponseData(
    statusCode: 200,
    headers: [...],
    contentLength: 1024,
    contentType: 'text/html',
    responseTime: 45.5
);

// Получить собранные данные
$data = $collector->getData();
```

### Структура данных

```php
[
    'status_code' => 200,
    'status_text' => 'OK',
    'headers' => [
        'Content-Type' => 'text/html',
        'Cache-Control' => 'no-cache',
    ],
    'content_type' => 'text/html; charset=UTF-8',
    'content_length' => 15728,
    'response_time' => 45.3,
    'protocol' => 'HTTP/1.1',
]
```

## Интеграция с другими коллекторами

Response Collector отлично работает с:

- **Request Collector** - показывает запрос и ответ
- **Overview Collector** - общая статистика включает response time
- **Routes Collector** - видно какой маршрут дал какой ответ

## Performance

Response Collector имеет **минимальное влияние**:

- ✅ Работает только в Debug режиме
- ✅ Использует встроенные PHP функции
- ✅ Не добавляет overhead к response time
- ✅ Легкий рендеринг HTML

**Overhead:** < 1ms

## Troubleshooting

### Headers не отображаются

Возможная причина: headers уже отправлены до вызова `headers_list()`.

**Решение:** Убедитесь, что Debug Toolbar рендерится в конце response.

### Response time = null

Возможная причина: константа `APP_START` или `VILNIUS_START` не определена.

**Решение:** Определите в начале `index.php`:

```php
define('APP_START', microtime(true));
```

### Status code = 200 всегда

Возможная причина: `http_response_code()` вызывается до установки реального кода.

**Решение:** Используйте `setResponseData()` для установки вручную.

## Best Practices

### ✅ Рекомендуется

1. Проверяйте Response Collector при debugging HTTP errors
2. Следите за response time (< 100ms хорошо)
3. Проверяйте правильность headers (особенно Cache-Control)
4. Используйте для API debugging

### ❌ Не рекомендуется

1. Не полагайтесь на Response Collector в production
2. Не оптимизируйте только по response time из toolbar

## Примеры использования

### 1. API Response

```php
// API endpoint
header('Content-Type: application/json');
http_response_code(200);
echo json_encode(['status' => 'success']);

// Response Collector покажет:
// Status: 200 OK
// Content-Type: application/json
```

### 2. Redirect Response

```php
http_response_code(302);
header('Location: /dashboard');
exit;

// Response Collector покажет:
// Status: 302 Found
// Location: /dashboard
```

### 3. Error Response

```php
http_response_code(500);
echo "Internal Server Error";

// Response Collector покажет:
// Status: 500 Internal Server Error
// Категория: Server Error
```

## См. также

- [Request Collector](RequestCollector.md) - информация о запросе
- [Debug Toolbar](DebugToolbar.md) - основная документация
- [Custom Collectors](DebugToolbarCollectors.md) - создание своих коллекторов

