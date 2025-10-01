# Улучшения Debug Toolbar Middleware

## Обзор изменений

Debug Toolbar Middleware был улучшен для более чистой архитектуры и надежной работы.

## Что было улучшено

### 1. Упрощенная логика output buffering

**До:**
```php
public function handle(callable $next): mixed
{
    $result = $next();
    
    if (!Environment::isDebug()) {
        return $result;
    }
    
    // Проверяем буфер
    if (ob_get_level() === 0) {
        ob_start();
    }
    
    if ($result !== null) {
        echo $result;
    }
    
    $output = ob_get_clean();
    // ...
}
```

**После:**
```php
public function handle(callable $next): mixed
{
    // Ранний выход если не debug режим
    if (!Environment::isDebug()) {
        return $next();
    }
    
    // Начинаем перехват вывода ДО выполнения запроса
    ob_start();
    
    // Выполняем запрос
    $result = $next();
    
    // Получаем весь вывод
    $output = ob_get_clean();
    
    // Модифицируем и выводим
    if (!empty($output)) {
        echo $this->injectDebugToolbar($output);
    }
    
    return $result;
}
```

**Преимущества:**
- ✅ Более простая и понятная логика
- ✅ Ранний выход для production режима (без лишних операций)
- ✅ Буфер открывается ДО выполнения запроса, а не после
- ✅ Меньше условных проверок

### 2. Разделение логики на методы

**Было:** Вся логика проверки Content-Type в одном методе

**Стало:** Разделено на отдельные методы с четкой ответственностью

```php
protected function injectDebugToolbar(string $content): string
{
    if (stripos($content, '</body>') === false) {
        return $content;
    }
    
    if (!$this->isHtmlResponse()) {
        return $content;
    }
    
    $toolbar = $this->renderDebugToolbar();
    
    if (empty($toolbar)) {
        return $content;
    }
    
    return str_ireplace('</body>', $toolbar . '</body>', $content);
}
```

**Новые методы:**

#### `isHtmlResponse()` - Проверка типа ответа
```php
protected function isHtmlResponse(): bool
{
    if (headers_sent()) {
        return true;
    }
    
    $headers = headers_list();
    
    foreach ($headers as $header) {
        if (stripos($header, 'Content-Type:') === 0) {
            return stripos($header, 'text/html') !== false;
        }
    }
    
    return true; // Default PHP Content-Type
}
```

#### `renderDebugToolbar()` - Безопасный рендеринг
```php
protected function renderDebugToolbar(): string
{
    if (!function_exists('render_debug_toolbar')) {
        return '';
    }
    
    try {
        return render_debug_toolbar();
    } catch (\Throwable $e) {
        if (Environment::isDevelopment()) {
            return '<!-- Debug Toolbar Error: ' . htmlspecialchars($e->getMessage()) . ' -->';
        }
        return '';
    }
}
```

**Преимущества:**
- ✅ Каждый метод имеет одну ответственность
- ✅ Легче тестировать отдельные части
- ✅ Код более читаемый и поддерживаемый
- ✅ Обработка ошибок при рендеринге toolbar

### 3. Обработка ошибок

Добавлена безопасная обработка ошибок при рендеринге Debug Toolbar:

```php
try {
    return render_debug_toolbar();
} catch (\Throwable $e) {
    // В development показываем ошибку в комментарии
    if (Environment::isDevelopment()) {
        return '<!-- Debug Toolbar Error: ' . htmlspecialchars($e->getMessage()) . ' -->';
    }
    // В production молча игнорируем
    return '';
}
```

**Преимущества:**
- ✅ Приложение не сломается если toolbar выбросит исключение
- ✅ В development видна ошибка в HTML комментарии
- ✅ В production ошибка не влияет на работу

### 4. Добавлен use Response

```php
use Core\Environment;
use Core\Response;  // Добавлено для возможных будущих улучшений
```

Подготовка к возможной работе с Response объектами напрямую в будущем.

## Архитектурные преимущества

### Единая точка ответственности

Debug Toolbar теперь полностью инъектируется **только** через middleware:

```
┌─────────────────────────────────────────┐
│         Middleware Pipeline             │
├─────────────────────────────────────────┤
│  DebugToolbarMiddleware                 │  ← Единственное место инъекции
│    ↓ ob_start()                         │
│  CsrfMiddleware                         │
│    ↓                                    │
│  AuthMiddleware                         │
│    ↓                                    │
│  Controller → Response → send()         │  ← Знают только о своей работе
│    ↑                                    │
│  TemplateEngine::render()               │  ← Знает только о шаблонах
└─────────────────────────────────────────┘
```

### Чистые классы

**Response** больше не знает о Debug Toolbar:
```php
// core/Response.php
public function send(): void
{
    http_response_code($this->statusCode);
    
    foreach ($this->headers as $name => $value) {
        header("{$name}: {$value}");
    }
    
    echo $this->content;  // Просто выводим контент
}
```

**TemplateEngine** больше не знает о Debug Toolbar:
```php
// core/TemplateEngine.php
public function display(string $template, array $variables = []): void
{
    $output = $this->render($template, $variables);
    echo $output;  // Просто выводим результат
}
```

## Как это работает

### 1. Контроллер возвращает Response
```php
class HomeController extends Controller
{
    public function index(): Response
    {
        return $this->view('home', ['user' => $user]);
    }
}
```

### 2. Router вызывает Response::send()
```php
// core/Router.php - finalHandler
if ($result instanceof Response) {
    $result->send();  // Выводит HTML через echo
}
```

### 3. Middleware перехватывает вывод
```php
// core/Middleware/DebugToolbarMiddleware.php
ob_start();                          // 1. Начинаем перехват
$result = $next();                   // 2. Выполняем (Response::send())
$output = ob_get_clean();            // 3. Получаем HTML
echo $this->injectDebugToolbar($output); // 4. Модифицируем и выводим
```

### 4. Браузер получает HTML с Toolbar
```html
<!DOCTYPE html>
<html>
<body>
    <h1>Home Page</h1>
    <!-- Контент страницы -->
    
    <!-- Debug Toolbar автоматически добавлен -->
    <div id="debug-toolbar">...</div>
</body>
</html>
```

## Тестирование

### Unit тесты

```php
// tests/Unit/Core/Middleware/DebugToolbarMiddlewareTest.php

it('injects toolbar in HTML responses in debug mode', function () {
    Environment::set('APP_ENV', 'development');
    $middleware = new DebugToolbarMiddleware();
    
    $next = fn() => '<html><body>Content</body></html>';
    
    ob_start();
    $middleware->handle($next);
    $output = ob_get_clean();
    
    expect($output)->toContain('<body>Content');
    expect($output)->toContain('debug-toolbar');
});

it('does not inject toolbar in JSON responses', function () {
    Environment::set('APP_ENV', 'development');
    header('Content-Type: application/json');
    
    $middleware = new DebugToolbarMiddleware();
    $next = fn() => json_encode(['data' => 'value']);
    
    ob_start();
    $middleware->handle($next);
    $output = ob_get_clean();
    
    expect($output)->not->toContain('debug-toolbar');
});

it('does not inject toolbar in production', function () {
    Environment::set('APP_ENV', 'production');
    $middleware = new DebugToolbarMiddleware();
    
    $next = fn() => '<html><body>Content</body></html>';
    
    ob_start();
    $middleware->handle($next);
    $output = ob_get_clean();
    
    expect($output)->not->toContain('debug-toolbar');
});

it('handles toolbar render errors gracefully', function () {
    Environment::set('APP_ENV', 'development');
    
    // Mock render_debug_toolbar to throw exception
    // ... тестовая логика
    
    // Приложение должно продолжить работу
});
```

## Производительность

### В Production режиме

```php
if (!Environment::isDebug()) {
    return $next();  // Ранний выход, 0 оверхеда
}
```

**Результат:** Нулевое влияние на производительность в production!

### В Development режиме

- Output buffering: минимальный оверхед
- Проверка Content-Type: O(n) где n = количество заголовков (обычно < 10)
- Инъекция toolbar: одна операция str_ireplace()

**Результат:** Незначительное влияние, приемлемое для development.

## Migration Guide

### Не требуется миграция!

Все существующие контроллеры и код работают без изменений:

```php
// ✅ Работает как раньше
public function index()
{
    return view('home');
}

// ✅ Работает как раньше
public function show(): Response
{
    return $this->view('post', compact('post'));
}

// ✅ Работает как раньше
public function api(): Response
{
    return $this->json(['data' => $data]);
}
```

## Checklist для других проектов

Если хотите применить эту архитектуру в других проектах:

- [ ] Создать `DebugToolbarMiddleware` с методом `handle()`
- [ ] Зарегистрировать как **первый** глобальный middleware
- [ ] Удалить инъекцию toolbar из Response::send()
- [ ] Удалить инъекцию toolbar из TemplateEngine::display()
- [ ] Проверить, что middleware перехватывает весь output
- [ ] Добавить проверку Content-Type для HTML
- [ ] Добавить обработку ошибок при рендеринге
- [ ] Написать unit тесты
- [ ] Обновить документацию

## Заключение

Middleware архитектура для Debug Toolbar — это **правильное решение**, которое:

- 🎯 Следует принципам SOLID (Single Responsibility)
- 🎯 Обеспечивает чистую архитектуру
- 🎯 Легко тестируется
- 🎯 Не имеет оверхеда в production
- 🎯 Расширяемо и поддерживаемо

**До улучшений:** Debug toolbar был размазан по Response и TemplateEngine (костыли)
**После улучшений:** Debug toolbar в единой точке — DebugToolbarMiddleware (чистая архитектура)

Enjoy your clean code! 🚀

