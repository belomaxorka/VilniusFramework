# Миграция: Очистка от лишних хелперов

## Обзор изменений

В рамках улучшения архитектуры фреймворка мы удаляем лишние хелперы, заменяя их прямыми вызовами методов классов.

## Философия

**До:**
- ❌ Множество хелперов-оберток над классами
- ❌ Дублирование функциональности
- ❌ Сложно понять, где находится реальная логика
- ❌ Хелперы для рендеринга, которые теперь не нужны (есть Middleware)

**После:**
- ✅ Минимум хелперов - только для основных операций
- ✅ Прямые вызовы методов классов (чище и понятнее)
- ✅ Debug Toolbar автоматически через Middleware
- ✅ Код более явный и легче читается

## Удаленные хелперы

### 1. Debug Output хелперы

#### ❌ Удалено

```php
// Устаревшие хелперы из core/helpers/debug/output.php
debug_flush();              // ❌ Удален
debug_output();             // ❌ Удален
has_debug_output();         // ❌ Удален
debug_render_on_page();     // ❌ Удален
render_debug();             // ❌ Удален
render_debug_toolbar();     // ❌ Удален
```

#### ✅ Используйте вместо них

```php
use Core\Debug;
use Core\DebugToolbar;

// Прямые вызовы классов
Debug::flush();
Debug::getOutput();
Debug::hasOutput();
Debug::setRenderOnPage(true);

// Debug Toolbar теперь автоматически через Middleware!
// Ничего не нужно вызывать вручную
```

### 2. View хелперы

#### ❌ Удалено

```php
// Устаревший хелпер из core/helpers/app/view.php
display('template', $data);  // ❌ Удален
```

#### ✅ Используйте вместо него

```php
use Core\TemplateEngine;

// Вариант 1: Прямой вызов (если нужен)
TemplateEngine::getInstance()->display('template', $data);

// Вариант 2: Через контроллер (рекомендуется)
public function index(): Response
{
    return $this->view('template', $data);
}

// Вариант 3: view() хелпер (оставлен как основной)
echo view('template', $data);
```

## Оставшиеся хелперы (основные)

Эти хелперы остаются, так как они действительно полезны:

### ✅ Конфигурация и окружение

```php
// core/helpers/app/config.php
config('app.name');              // Удобно
env('APP_ENV', 'production');    // Удобно

// Альтернатива (менее удобно)
Config::get('app.name');
Env::get('APP_ENV', 'production');
```

### ✅ Язык и локализация

```php
// core/helpers/app/lang.php
__('messages.welcome');          // Удобно и понятно
trans('auth.failed');            // Удобно

// Альтернатива (менее удобно)
Lang::get('messages.welcome');
```

### ✅ Шаблоны

```php
// core/helpers/app/view.php
view('home', ['user' => $user]); // Удобно и часто используется

// Альтернатива (длиннее)
TemplateEngine::getInstance()->render('home', ['user' => $user]);
```

### ✅ HTTP и Response

```php
// core/helpers/app/http.php
request('email');                // Удобно
request();                       // Получить Request объект
response()->json(['data' => 1]); // Fluent interface
json(['data' => 1]);             // Короче
redirect('/home');               // Короче
back();                          // Короче
abort(404);                      // Короче и понятнее
```

### ✅ Роутинг

```php
// core/helpers/app/route.php
route('home');                   // Удобно
route('user.show', ['id' => 1]); // Понятно
```

### ✅ CSRF

```php
// core/helpers/app/csrf.php
csrf_token();                    // Короче
csrf_field();                    // Генерирует hidden input
```

### ✅ Debug основные

```php
// core/helpers/debug/dump.php
dump($var);                      // Короче и удобнее
dd($var);                        // Die and dump
dump_pretty($var);               // Красивый вывод

// core/helpers/debug/trace.php
debug_trace();                   // Удобно
debug_backtrace_pretty();        // Удобно
```

## Руководство по миграции

### Шаг 1: Удалите ручной рендеринг Debug Toolbar

**До:**
```php
<!-- В шаблоне layout.php -->
<!DOCTYPE html>
<html>
<body>
    <?= $content ?>
    <?= render_debug_toolbar() ?> <!-- ❌ Удалите это -->
</body>
</html>
```

**После:**
```php
<!-- В шаблоне layout.php -->
<!DOCTYPE html>
<html>
<body>
    <?= $content ?>
    <!-- Toolbar добавится автоматически через Middleware! -->
</body>
</html>
```

### Шаг 2: Замените display() на view()

**До:**
```php
public function index()
{
    display('home', ['user' => $user]); // ❌ Устарело
}
```

**После:**
```php
public function index(): Response
{
    return $this->view('home', ['user' => $user]); // ✅ Современно
}

// Или
public function index()
{
    echo view('home', ['user' => $user]); // ✅ Тоже хорошо
}
```

### Шаг 3: Замените debug хелперы на методы классов

**До:**
```php
debug_flush();                  // ❌
debug_output();                 // ❌
has_debug_output();             // ❌
debug_render_on_page(false);    // ❌
```

**После:**
```php
use Core\Debug;

Debug::flush();                 // ✅
Debug::getOutput();             // ✅
Debug::hasOutput();             // ✅
Debug::setRenderOnPage(false);  // ✅
```

### Шаг 4: Проверьте DebugToolbarMiddleware

Убедитесь, что middleware зарегистрирован:

```php
// config/middleware.php
'global' => [
    \Core\Middleware\DebugToolbarMiddleware::class, // ✅ Должен быть первым
],
```

## Преимущества после миграции

### 1. Чистота кода

**До:**
```php
// Не понятно, что делает хелпер
render_debug_toolbar();
debug_flush();
display('home');
```

**После:**
```php
// Явно видно, что вызываются методы классов
DebugToolbar::render();  // Но обычно не нужно - есть Middleware!
Debug::flush();
TemplateEngine::getInstance()->display('home');
```

### 2. IDE поддержка

Прямые вызовы классов лучше работают с автодополнением и навигацией в IDE:

```php
Debug::  // ← IDE покажет все доступные методы
Debug::flush();  // ← Ctrl+Click перейдет к определению
```

### 3. Меньше магии

Код становится более явным и понятным для новых разработчиков.

### 4. Проще рефакторинг

Легче найти все использования метода через "Find Usages" в IDE.

## Таблица миграции

| Устаревший хелпер | Замена |
|------------------|---------|
| `render_debug_toolbar()` | **Middleware** (автоматически) |
| `render_debug()` | `Debug::getOutput()` |
| `debug_flush()` | `Debug::flush()` |
| `debug_output()` | `Debug::getOutput()` |
| `has_debug_output()` | `Debug::hasOutput()` |
| `debug_render_on_page()` | `Debug::setRenderOnPage()` |
| `display($template, $data)` | `$this->view($template, $data)` в контроллере |

| Оставшиеся хелперы | Используйте |
|-------------------|-------------|
| `view()` | ✅ Используйте |
| `config()` | ✅ Используйте |
| `env()` | ✅ Используйте |
| `__()` / `trans()` | ✅ Используйте |
| `request()` | ✅ Используйте |
| `response()` | ✅ Используйте |
| `json()` | ✅ Используйте |
| `redirect()` | ✅ Используйте |
| `route()` | ✅ Используйте |
| `csrf_token()` | ✅ Используйте |
| `dump()` / `dd()` | ✅ Используйте |

## Проверка после миграции

### 1. Запустите тесты

```bash
./vendor/bin/pest
```

### 2. Проверьте Debug Toolbar

- Откройте любую страницу в development режиме
- Toolbar должен появиться внизу автоматически

### 3. Поиск устаревших вызовов

```bash
# Найти использования удаленных хелперов
grep -r "render_debug_toolbar" app/
grep -r "display(" app/
grep -r "debug_flush" app/
```

## FAQ

**Q: Почему удалили `display()` но оставили `view()`?**

A: `view()` возвращает строку и часто используется в коде. `display()` выводил напрямую, что менее гибко. Лучше использовать `$this->view()` в контроллерах.

**Q: Что делать с существующим кодом, использующим `render_debug_toolbar()`?**

A: Просто удалите эти вызовы. Debug Toolbar теперь добавляется автоматически через Middleware.

**Q: Можно ли вернуть удаленные хелперы?**

A: Не рекомендуется. Используйте прямые вызовы классов - это чище и понятнее.

**Q: Как отключить Debug Toolbar?**

A: Установите `APP_ENV=production` в `.env` или уберите `DebugToolbarMiddleware` из `config/middleware.php`.

## Заключение

Очистка от лишних хелперов делает код:
- ✅ Чище и понятнее
- ✅ Легче для поддержки
- ✅ Лучше работает с IDE
- ✅ Меньше магии, больше ясности

**Основные хелперы остались** для удобства (config, env, lang, view, request, route и т.д.)

**Debug Toolbar теперь автоматически** через Middleware - никаких хелперов не нужно!

---

**Дата миграции:** 2025-10-01  
**Версия:** 1.0.0

