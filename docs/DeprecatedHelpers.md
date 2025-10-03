# Устаревшие Helper-функции

## ⚠️ Мажорное изменение: Упрощение хелперов

С целью улучшения читаемости кода, упрощения отладки и уменьшения магии, фреймворк теперь предоставляет только **критически важные helper-функции**.

## ✅ Доступные хелперы

Осталось только 4 критичных helper-функции:

| Функция | Описание |
|---------|----------|
| `config($key, $default = null)` | Получить значение конфигурации |
| `env($key, $default = null)` | Получить переменную окружения |
| `__($key, $params = [])` | Получить переведенную строку |
| `vite($entry = 'app')` | Подключить Vite assets |

## 🗑️ Удаленные хелперы

### HTTP хелперы → Используйте методы Controller

| Удаленная функция | Замена | Описание |
|------------------|--------|----------|
| `request()` | `Request::getInstance()` или `$this->request` | Получить Request |
| `response()` | `Response::make()` или `$this->response` | Создать Response |
| `json()` | `$this->json()` | JSON ответ |
| `redirect()` | `$this->redirect()` | Редирект |
| `back()` | `$this->back()` | Редирект назад |
| `abort()` | `$this->error()` + status | Прервать с ошибкой |
| `abort_if()` | условие + `$this->error()` | Прервать если |
| `abort_unless()` | условие + `$this->error()` | Прервать если не |

**Было:**
```php
function myRoute() {
    $name = request('name');
    return json(['message' => 'Hello ' . $name]);
}
```

**Стало:**
```php
class MyController extends Controller {
    public function myAction(): Response {
        $name = $this->request->input('name');
        return $this->json(['message' => 'Hello ' . $name]);
    }
}
```

---

### View хелперы → Используйте методы Controller

| Удаленная функция | Замена | Описание |
|------------------|--------|----------|
| `view()` | `$this->view()` | Отрендерить view |
| `display()` | `$this->view()` | Отрендерить и вывести |
| `template()` | `TemplateEngine::getInstance()` | Получить движок шаблонов |

**Было:**
```php
echo view('welcome', ['name' => 'John']);
```

**Стало:**
```php
class HomeController extends Controller {
    public function index(): Response {
        return $this->view('welcome', ['name' => 'John']);
    }
}
```

---

### Container хелперы → Используйте Container напрямую

| Удаленная функция | Замена | Описание |
|------------------|--------|----------|
| `app()` | `Container::getInstance()` | Получить контейнер |
| `resolve()` | `Container::getInstance()->make()` | Resolve класс |
| `singleton()` | `Container::getInstance()->singleton()` | Зарегистрировать singleton |

**Было:**
```php
$db = app(Database::class);
singleton(CacheManager::class);
```

**Стало:**
```php
use Core\Container;

$db = Container::getInstance()->make(Database::class);
Container::getInstance()->singleton(CacheManager::class);
```

---

### Route хелперы → Используйте Router напрямую

| Удаленная функция | Замена | Описание |
|------------------|--------|----------|
| `route()` | `Router::route()` или `$this->redirectRoute()` | Генерация URL по имени роута |

**Было:**
```php
<a href="<?= route('user.profile', ['id' => 123]) ?>">Profile</a>
```

**Стало:**
```php
use Core\Router;

<a href="<?= Router::getInstance()->route('user.profile', ['id' => 123]) ?>">Profile</a>

// Или в контроллере:
return $this->redirectRoute('user.profile', ['id' => 123]);
```

---

### CSRF хелперы → Используйте Session напрямую

| Удаленная функция | Замена | Описание |
|------------------|--------|----------|
| `csrf_token()` | `Session::generateCsrfToken()` | Получить CSRF токен |
| `csrf_field()` | HTML вручную + `Session::generateCsrfToken()` | Скрытое поле |
| `csrf_meta()` | HTML вручную + `Session::generateCsrfToken()` | Meta тег |

**Было:**
```php
<form>
    <?= csrf_field() ?>
    ...
</form>
```

**Стало:**
```php
use Core\Session;

<form>
    <input type="hidden" name="_csrf_token" value="<?= Session::generateCsrfToken() ?>">
    ...
</form>
```

---

### Debug хелперы → Используйте Debug классы напрямую

| Удаленная группа | Замена | Описание |
|-----------------|--------|----------|
| `dd()`, `dump()` | `Debug::dump()`, `Debug::dd()` | Дамп переменных |
| `trace()` | `Debug::trace()` | Stack trace |
| `collect()` | `Debug::collect()` | Сбор debug данных |
| `debug_output()` | `Debug::getOutput()` | Получить вывод |
| `debug_flush()` | `Debug::flush()` | Очистить буфер |
| `has_debug_output()` | `Debug::hasOutput()` | Проверка наличия |
| `log_*()` | `Logger::*()` | Логирование |
| `dump_server()` | `DumpServer::start()` | Dump server |

**Было:**
```php
dd($variable);
dump($data);
log_info('User logged in');
```

**Стало:**
```php
use Core\Debug;
use Core\Logger;

Debug::dd($variable);
Debug::dump($data);
Logger::info('User logged in');
```

---

### Cache хелперы → Используйте Cache напрямую

| Удаленная функция | Замена | Описание |
|------------------|--------|----------|
| `cache()` | `Cache::get()` | Получить из кеша |
| `cache_remember()` | `Cache::remember()` | Получить или создать |
| `cache_forget()` | `Cache::forget()` | Удалить из кеша |
| `cache_flush()` | `Cache::flush()` | Очистить весь кеш |

**Было:**
```php
$users = cache_remember('users', 3600, fn() => User::all());
cache_forget('users');
```

**Стало:**
```php
use Core\Cache;

$users = Cache::remember('users', 3600, fn() => User::all());
Cache::forget('users');
```

---

### Emailer хелперы → Используйте Emailer напрямую

| Удаленная функция | Замена | Описание |
|------------------|--------|----------|
| `emailer()` | `Emailer::getInstance()` | Получить Emailer |
| `send_email()` | `Emailer::getInstance()->send()` | Отправить email |
| `send_email_view()` | `Emailer::getInstance()->sendView()` | Отправить с view |

**Было:**
```php
send_email('test@example.com', 'Subject', 'Body');
send_email_view('test@example.com', 'Subject', 'emails/welcome', ['name' => 'John']);
```

**Стало:**
```php
use Core\Emailer;

Emailer::getInstance()->send('test@example.com', 'Subject', 'Body');
Emailer::getInstance()->sendView('test@example.com', 'Subject', 'emails/welcome', ['name' => 'John']);
```

---

### Environment хелперы → Используйте Environment напрямую

| Удаленная функция | Замена | Описание |
|------------------|--------|----------|
| `is_debug()` | `Environment::isDebug()` | Проверка debug режима |
| `is_dev()` | `Environment::isDevelopment()` | Проверка dev окружения |
| `is_prod()` | `Environment::isProduction()` | Проверка prod окружения |
| `is_testing()` | `Environment::isTesting()` | Проверка test окружения |

**Было:**
```php
if (is_debug()) {
    // debug code
}
```

**Стало:**
```php
use Core\Environment;

if (Environment::isDebug()) {
    // debug code
}
```

---

### Profiler хелперы → Используйте Profiler классы напрямую

| Удаленная функция | Замена | Описание |
|------------------|--------|----------|
| `timer_start()` | `DebugTimer::start()` | Запустить таймер |
| `timer_stop()` | `DebugTimer::stop()` | Остановить таймер |
| `timer_get()` | `DebugTimer::get()` | Получить время |
| `memory_usage()` | `MemoryProfiler::getUsage()` | Использование памяти |
| `benchmark()` | `DebugTimer::measure()` | Бенчмарк функции |

**Было:**
```php
timer_start('operation');
// ... code ...
timer_stop('operation');
echo timer_get('operation');
```

**Стало:**
```php
use Core\DebugTimer;

DebugTimer::start('operation');
// ... code ...
DebugTimer::stop('operation');
echo DebugTimer::get('operation');
```

---

### Database хелперы → Используйте QueryDebugger напрямую

| Удаленная функция | Замена | Описание |
|------------------|--------|----------|
| `query_log()` | `QueryDebugger::getLog()` | Получить лог запросов |
| `query_stats()` | `QueryDebugger::getStats()` | Получить статистику |
| `query_count()` | `QueryDebugger::getCount()` | Количество запросов |

**Было:**
```php
$queries = query_log();
$stats = query_stats();
```

**Стало:**
```php
use Core\QueryDebugger;

$queries = QueryDebugger::getLog();
$stats = QueryDebugger::getStats();
```

---

### Context хелперы → Используйте DebugContext напрямую

| Удаленная функция | Замена | Описание |
|------------------|--------|----------|
| `context_start()` | `DebugContext::start()` | Начать контекст |
| `context_end()` | `DebugContext::end()` | Закончить контекст |
| `context_run()` | `DebugContext::run()` | Выполнить с контекстом |

**Было:**
```php
context_start('Database Query');
// ... code ...
context_end();
```

**Стало:**
```php
use Core\DebugContext;

DebugContext::start('Database Query');
// ... code ...
DebugContext::end();
```

---

## 📖 Преимущества прямого использования классов

1. **Явность** - сразу понятно, из какого класса вызывается метод
2. **IDE поддержка** - автодополнение и переход к определению работают лучше
3. **Типизация** - PHPStan/Psalm лучше анализируют статические вызовы
4. **Производительность** - нет overhead на загрузку множества helper файлов
5. **Простота** - меньше магии, проще понять что происходит
6. **Меньше глобального состояния** - легче тестировать и поддерживать

## 🎯 Философия фреймворка

**Меньше магии — больше ясности**

Хелперы были полезны на начальном этапе, но они создают слишком много неявных зависимостей и усложняют понимание кода. Фреймворк теперь фокусируется на:

- ✅ Явных зависимостях
- ✅ Чистой архитектуре
- ✅ Простоте и понятности
- ✅ Лучшей поддержке IDE

Оставлены только те хелперы, которые действительно критичны и используются повсеместно: `config()`, `env()`, `__()`, `vite()`.
