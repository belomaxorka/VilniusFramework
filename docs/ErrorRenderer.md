# ErrorRenderer - Централизованный рендеринг ошибок

## Обзор

`ErrorRenderer` - класс для централизованного рендеринга страниц ошибок с автоматической интеграцией Debug Toolbar.

### Возможности:
- 🎨 **Простой минималистичный дизайн** - серый фон, текст по центру
- 🐛 **Автоматический Debug Toolbar** - отображается на страницах ошибок в debug режиме
- 📱 **JSON и HTML поддержка** - автоматическое определение формата
- 🎯 **Пользовательские шаблоны** - возможность создать свои страницы ошибок
- 🔧 **Детали для разработчиков** - дополнительная информация в debug режиме

## Использование

### Базовое использование

```php
use Core\ErrorRenderer;

// Простая ошибка
echo ErrorRenderer::render(404, 'Not Found');

// Ошибка 500
echo ErrorRenderer::render(500, 'Internal Server Error');
```

### В Router

```php
// 404 ошибка
protected function renderDefaultNotFound(string $method, string $uri): void
{
    echo ErrorRenderer::render(404, 'Not Found');
}
```

### В ErrorHandler

```php
// Ошибка 500
echo ErrorRenderer::render(500, 'Internal Server Error');
```

## Пользовательские шаблоны

### Создание шаблона

Создайте файл `resources/views/errors/{code}.tpl`:

```php
// resources/views/errors/404.tpl
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Not Found</title>
    <style>
        body {
            background: #9e9e9e;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .error-code {
            font-size: 120px;
            color: #212121;
        }
    </style>
</head>
<body>
    <div>
        <div class="error-code">404</div>
        <div>404 | Not Found</div>
    </div>
</body>
</html>
```

### Доступные коды

Создайте шаблоны для любых HTTP кодов:
- `400.tpl` - Bad Request
- `401.tpl` - Unauthorized
- `403.tpl` - Forbidden
- `404.tpl` - Not Found
- `422.tpl` - Unprocessable Entity
- `500.tpl` - Internal Server Error
- и т.д.

## Debug Toolbar

Debug Toolbar **автоматически** добавляется на все страницы ошибок в debug режиме.

### Преимущества:
- ✅ Видно все debug данные на странице ошибки
- ✅ Можно посмотреть SQL запросы, логи, память
- ✅ Помогает быстрее найти причину ошибки

### Пример

В debug режиме страница 404 будет выглядеть так:

```
┌─────────────────────────────┐
│         404                 │
│    404 | Not Found           │
└─────────────────────────────┘

┌─────────────────────────────┐
│ 🐛 Debug Toolbar            │
│ ⏱️ 45ms  💾 2.1MB  🗄️ 3     │
│ ├─ Request                  │
│ ├─ Response                 │
│ └─ ...                      │
└─────────────────────────────┘
```

## JSON Ответы

Для JSON запросов автоматически возвращается JSON:

```php
// Запрос: Accept: application/json
ErrorRenderer::render(404, 'Not Found');

// Ответ:
{
    "error": "Not Found",
    "message": "Not Found",
    "code": 404,
    "path": "/api/users"
}
```

### Определение JSON запроса

ErrorRenderer определяет JSON запрос по:
1. `Content-Type: application/json`
2. `Accept: application/json`
3. AJAX запрос + JSON в Accept

## Стандартный дизайн

Все страницы ошибок используют минималистичный дизайн:

- **Фон**: серый (#9e9e9e)
- **Код ошибки**: большой, черный (120px)
- **Формат**: `{код} | {сообщение}`
- **Расположение**: по центру экрана

### Пример

```
┌─────────────────────────────┐
│                             │
│          404                │
│     404 | Not Found          │
│                             │
└─────────────────────────────┘
```

## Простой дизайн

Страницы ошибок имеют минималистичный дизайн:

```php
ErrorRenderer::render(404, 'Not Found');
```

Результат:
```
┌─────────────────────────────┐
│          404                │
│     404 | Not Found          │
└─────────────────────────────┘
```

## API

### `render(int $code, string $message): string`

Рендерит страницу ошибки.

**Параметры:**
- `$code` - HTTP статус код (404, 500, и т.д.)
- `$message` - Сообщение об ошибке

**Возвращает:** HTML или JSON строку

**Примеры:**

```php
// Простая ошибка
ErrorRenderer::render(404, 'Not Found');

// Ошибка 500
ErrorRenderer::render(500, 'Database Error');

// Пользовательская ошибка
ErrorRenderer::render(418, "I'm a teapot");
```

## Интеграция

### В роутах

```php
// routes/web.php
$router->get('admin', function() {
    echo ErrorRenderer::render(403, 'Access Denied');
});
```

### В контроллерах

```php
// app/Controllers/UserController.php
public function show(int $id)
{
    $user = User::find($id);
    
    if (!$user) {
        echo ErrorRenderer::render(404, 'User Not Found');
        return;
    }
    
    return view('user.show', compact('user'));
}
```

### В middleware

```php
// core/Middleware/AdminMiddleware.php
public function handle(callable $next): mixed
{
    if (!isAdmin()) {
        echo ErrorRenderer::render(403, 'Admin Access Required');
        exit;
    }
    
    return $next();
}
```

## Коды ошибок

### Поддерживаемые коды

| Код | Название | Использование |
|-----|----------|---------------|
| 400 | Bad Request | Некорректный запрос |
| 401 | Unauthorized | Требуется авторизация |
| 403 | Forbidden | Доступ запрещен |
| 404 | Not Found | Ресурс не найден |
| 405 | Method Not Allowed | Метод не поддерживается |
| 422 | Unprocessable Entity | Ошибка валидации |
| 429 | Too Many Requests | Превышен лимит запросов |
| 500 | Internal Server Error | Ошибка сервера |
| 502 | Bad Gateway | Ошибка шлюза |
| 503 | Service Unavailable | Сервис недоступен |

### Пользовательские коды

Можно использовать любой HTTP код:

```php
ErrorRenderer::render(418, "I'm a teapot");
ErrorRenderer::render(451, 'Unavailable For Legal Reasons');
```

## FAQ

**Q: Как отключить Debug Toolbar на странице ошибки?**

A: Используйте production режим:
```php
// .env
APP_ENV=production
```

**Q: Можно ли изменить цвет фона?**

A: Да, создайте пользовательский шаблон в `resources/views/errors/{code}.tpl`

**Q: Как добавить кнопку "На главную"?**

A: Создайте пользовательский шаблон:
```html
<a href="/" class="btn">На главную</a>
```

**Q: Работает ли с API endpoints?**

A: Да! Автоматически возвращает JSON для API запросов.

## См. также

- [Router.md](Router.md) - Маршрутизация
- [ErrorHandler.md](ErrorHandler.md) - Обработка ошибок
- [DebugToolbar.md](DebugToolbar.md) - Debug панель

