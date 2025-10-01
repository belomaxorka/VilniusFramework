# Debug Toolbar Middleware

Middleware для автоматической инъекции Debug Toolbar в HTML ответы.

## Описание

`DebugToolbarMiddleware` — это глобальный middleware, который автоматически внедряет Debug Toolbar в HTML страницы в режиме разработки.

## Преимущества архитектуры

### Почему Middleware?

Раньше инъекция Debug Toolbar происходила в двух местах:
1. В `TemplateEngine::display()` — для шаблонов
2. В `Response::send()` — для Response объектов

Это было **неправильно**, потому что:
- ❌ Логика была размазана по разным классам
- ❌ Дублирование кода
- ❌ Нарушение принципа единственной ответственности
- ❌ Сложно тестировать и поддерживать

### Решение: Global Middleware

Middleware — это **правильное место** для инъекции Debug Toolbar, потому что:
- ✅ **Единая точка ответственности** — вся логика в одном месте
- ✅ **Чистая архитектура** — TemplateEngine и Response не знают о Debug Toolbar
- ✅ **Легко включать/отключать** — просто убрать из глобальных middleware
- ✅ **Работает для любых ответов** — независимо от того, как генерируется HTML
- ✅ **Следует паттерну middleware** — обработка после выполнения запроса

## Как это работает

### 1. Регистрация

```php
// config/middleware.php
return [
    'global' => [
        \Core\Middleware\DebugToolbarMiddleware::class,
    ],
];
```

### 2. Автоматическая работа

Middleware выполняется **для всех запросов** и:

1. Пропускает запрос дальше через `$next()`
2. Перехватывает output через output buffering
3. Проверяет условия для инъекции
4. Внедряет Debug Toolbar перед `</body>`

### 3. Условия инъекции

Debug Toolbar внедряется только если:

```php
✅ Environment::isDebug() === true
✅ Есть закрывающий тег </body>
✅ Content-Type: text/html (или не установлен)
```

Не внедряется если:

```php
❌ Production режим
❌ JSON/XML/API ответы
❌ Нет тега </body>
❌ Content-Type не HTML
```

## Пример использования

### Контроллер

```php
namespace App\Controllers;

use Core\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        // Просто возвращаем view
        // Debug Toolbar будет добавлен автоматически
        return $this->view('home');
    }

    public function api(): Response
    {
        // Это JSON — Debug Toolbar НЕ будет добавлен
        return $this->json(['data' => 'value']);
    }
}
```

### Прямой HTML

```php
public function custom(): Response
{
    $html = '<html><body><h1>Hello</h1></body></html>';
    
    // Debug Toolbar будет автоматически внедрен
    return $this->html($html);
}
```

### Без Response (старый код)

```php
public function legacy(): void
{
    echo '<html><body><h1>Legacy</h1></body></html>';
    
    // Debug Toolbar будет автоматически внедрен
}
```

## Output Buffering

Middleware использует output buffering для перехвата вывода:

```php
public function handle(callable $next): mixed
{
    // Если не debug режим, пропускаем
    if (!Environment::isDebug()) {
        return $next();
    }

    // Начинаем перехват вывода
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

## Порядок выполнения

Global middleware выполняются **в порядке регистрации**:

```php
'global' => [
    DebugToolbarMiddleware::class,  // 1. Последний слой (output)
    ThrottleMiddleware::class,       // 2. Rate limiting
    CsrfMiddleware::class,           // 3. CSRF проверка
],
```

**Важно:** Debug Toolbar должен быть **первым**, потому что:
- Он обрабатывает output **после** всех остальных
- Middleware выполняются в обратном порядке для response

## Отключение Debug Toolbar

### Временно (для одного запроса)

```php
// В контроллере
public function raw(): Response
{
    $html = '<html><body><h1>Raw HTML</h1></body></html>';
    
    // Установить не-HTML Content-Type
    return response($html)
        ->header('Content-Type', 'text/plain');
}
```

### Глобально

```php
// config/middleware.php
'global' => [
    // Закомментировать или удалить
    // \Core\Middleware\DebugToolbarMiddleware::class,
],
```

### Через конфиг

```php
// .env
APP_ENV=production  # Debug Toolbar не будет работать
```

## Производительность

Debug Toolbar Middleware **не влияет** на производительность в production:

```php
if (!Environment::isDebug()) {
    return $result;  // Ранний выход
}
```

В production middleware сразу возвращает результат без обработки.

## Тестирование

```php
it('injects debug toolbar in HTML responses', function () {
    $middleware = new DebugToolbarMiddleware();
    
    $next = fn() => '<html><body>Content</body></html>';
    
    ob_start();
    $middleware->handle($next);
    $output = ob_get_clean();
    
    expect($output)->toContain('debug-toolbar');
});

it('does not inject toolbar in JSON responses', function () {
    header('Content-Type: application/json');
    
    $middleware = new DebugToolbarMiddleware();
    $next = fn() => json_encode(['data' => 'value']);
    
    ob_start();
    $middleware->handle($next);
    $output = ob_get_clean();
    
    expect($output)->not->toContain('debug-toolbar');
});
```

## Альтернативы (не рекомендуется)

### ❌ В TemplateEngine

```php
// Плохо: логика в шаблонизаторе
public function display(): void
{
    echo $output;
    
    if (Environment::isDebug()) {
        echo render_debug_toolbar();
    }
}
```

### ❌ В Response

```php
// Плохо: логика в Response
public function send(): void
{
    echo $this->content;
    
    if (Environment::isDebug()) {
        echo render_debug_toolbar();
    }
}
```

### ✅ В Middleware (правильно)

```php
// Хорошо: отдельная ответственность
class DebugToolbarMiddleware
{
    public function handle(callable $next)
    {
        $result = $next();
        // Обработка output
        return $result;
    }
}
```

## FAQ

**Q: Почему Debug Toolbar не появляется?**

A: Проверьте:
1. `APP_ENV=development` в `.env`
2. Есть ли `</body>` в HTML
3. Зарегистрирован ли middleware в `config/middleware.php`
4. Content-Type должен быть HTML

**Q: Можно ли добавить другие глобальные middleware?**

A: Да, просто добавьте в `config/middleware.php`:

```php
'global' => [
    DebugToolbarMiddleware::class,
    YourCustomMiddleware::class,
],
```

**Q: Влияет ли это на API endpoints?**

A: Нет, для JSON/XML ответов Debug Toolbar не внедряется.

**Q: Можно ли контролировать порядок middleware?**

A: Да, порядок в массиве `global` определяет порядок выполнения.

## Best Practices

1. **Debug Toolbar всегда первый** в списке global middleware
2. **Не внедряйте** Debug Toolbar вручную в контроллерах
3. **Используйте Environment::isDebug()** для проверки режима
4. **Не модифицируйте** TemplateEngine или Response для debug вывода
5. **Тестируйте** middleware отдельно от бизнес-логики

## Заключение

Middleware — это **правильное архитектурное решение** для инъекции Debug Toolbar:

- 🎯 Чистая архитектура
- 🎯 Единая ответственность
- 🎯 Легко тестировать
- 🎯 Просто поддерживать
- 🎯 Работает везде

Теперь Debug Toolbar работает **прозрачно** для всего приложения, без костылей в разных классах.

