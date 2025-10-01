# Helper Functions Documentation

Хелперы фреймворка организованы по группам в отдельных папках для удобства использования и поддержки.

## Структура хелперов

```
core/helpers/
├── app/         - Основные функции приложения
├── environment/ - Проверки окружения
├── debug/       - Отладочные функции
├── profiler/    - Профилирование производительности
├── database/    - Отладка базы данных
└── context/     - Контексты отладки
```

Все группы автоматически загружаются при старте приложения через `core/bootstrap.php`.

Подробнее о структуре: [core/helpers/README.md](../core/helpers/README.md)

### 📦 app.php - Основные функции приложения

Базовые функции для работы с конфигурацией, переводами, шаблонами и переменными окружения.

```php
config(string $key, mixed $default = null): mixed
// Получить значение из конфигурации

__(string $key, array $params = []): string
// Получить переведенную строку

env(string $key, mixed $default = null): mixed
// Получить переменную окружения

view(string $template, array $variables = []): string
// Отрендерить шаблон и вернуть как строку

display(string $template, array $variables = []): void
// Отрендерить и сразу вывести шаблон

template(): \Core\TemplateEngine
// Получить экземпляр движка шаблонов
```

**Примеры:**
```php
$dbHost = config('database.host', 'localhost');
echo __('welcome.message', ['name' => 'John']);
$apiKey = env('API_KEY');
$html = view('welcome', ['title' => 'Home']);
```

---

### 🌍 environment.php - Проверки окружения

Функции для определения текущего окружения и режима работы.

```php
is_debug(): bool
// Проверить, включен ли режим отладки

is_dev(): bool
// Проверить, является ли окружение разработкой

is_prod(): bool
// Проверить, является ли окружение продакшеном

is_testing(): bool
// Проверить, является ли окружение тестированием

is_staging(): bool
// Проверить, является ли окружение staging

app_env(): string
// Получить название текущего окружения

is_cli(): bool
// Проверить, запущено ли приложение в CLI

is_windows(): bool
// Проверить, работает ли на Windows

is_unix(): bool
// Проверить, работает ли на Unix-подобной системе
```

**Примеры:**
```php
if (is_debug()) {
    dump($data);
}

if (is_prod()) {
    // Отключить детальные ошибки
}

if (is_cli()) {
    echo "Running in command line\n";
}
```

---

### 🐛 debug.php - Отладочные функции

Основные функции для отладки и вывода информации.

```php
dd(mixed $var, ?string $label = null): never
// Вывести переменную и остановить выполнение

dump(mixed $var, ?string $label = null): void
// Вывести переменную без остановки

dump_pretty(mixed $var, ?string $label = null): void
// Красиво отформатированный вывод

dd_pretty(mixed $var, ?string $label = null): never
// Красивый вывод + остановка

collect(mixed $var, ?string $label = null): void
// Собрать данные для отладки без вывода

dump_all(bool $die = false): void
// Вывести все собранные данные

clear_debug(): void
// Очистить собранные данные

trace(?string $label = null): void
// Вывести backtrace

debug_log(string $message): void
// Записать в лог только в режиме отладки

debug_flush(): void
// Сбросить накопленные данные

debug_output(): string
// Получить все данные как строку

has_debug_output(): bool
// Проверить наличие данных

debug_render_on_page(bool $enabled = true): void
// Включить/выключить рендеринг на странице

render_debug(): string
// Отрендерить отладочный вывод

render_debug_toolbar(): string
// Отрендерить панель инструментов отладки

server_dump(mixed $data, ?string $label = null): bool
// Отправить данные на dump server

dd_server(mixed $data, ?string $label = null): never
// Отправить на dump server и остановиться

dump_server_available(): bool
// Проверить доступность dump server
```

**Примеры:**
```php
// Быстрая отладка
dd($user);

// Накопление данных
collect($query1, 'First Query');
collect($query2, 'Second Query');
dump_all(); // Вывести всё разом

// Трассировка
trace('After user update');

// Dump server (отладка без вывода на страницу)
server_dump($data, 'API Response');
```

---

### ⏱️ profiler.php - Профилирование производительности

Функции для измерения времени выполнения и использования памяти.

#### Benchmark
```php
benchmark(callable $callback, ?string $label = null): mixed
// Измерить время выполнения функции
```

#### Timer
```php
timer_start(string $name = 'default'): void
// Запустить таймер

timer_stop(string $name = 'default'): float
// Остановить таймер и получить время

timer_lap(string $name = 'default', ?string $label = null): float
// Сделать промежуточный замер

timer_elapsed(string $name = 'default'): float
// Получить прошедшее время без остановки

timer_dump(?string $name = null): void
// Вывести информацию о таймере(ах)

timer_clear(?string $name = null): void
// Очистить таймер(ы)

timer_measure(string $name, callable $callback): mixed
// Измерить время выполнения функции
```

#### Memory
```php
memory_start(): void
// Начать профилирование памяти

memory_snapshot(string $name, ?string $label = null): array
// Сделать снимок памяти

memory_current(): int
// Получить текущее использование памяти

memory_peak(): int
// Получить пиковое использование памяти

memory_dump(): void
// Вывести профиль памяти

memory_clear(): void
// Очистить снимки

memory_measure(string $name, callable $callback): mixed
// Измерить использование памяти функцией

memory_format(int $bytes, int $precision = 2): string
// Форматировать байты в читаемый вид
```

**Примеры:**
```php
// Простой бенчмарк
$result = benchmark(function() {
    // Тяжёлая операция
    return processData();
}, 'Data Processing');

// Многократные замеры
timer_start('request');
// ... код ...
timer_lap('request', 'After DB query');
// ... код ...
timer_lap('request', 'After rendering');
$total = timer_stop('request');
timer_dump('request');

// Профилирование памяти
memory_start();
memory_snapshot('start', 'Before loading');
$data = loadLargeData();
memory_snapshot('loaded', 'After loading');
memory_dump();
echo "Peak: " . memory_format(memory_peak());
```

---

### 🗄️ database.php - Отладка базы данных

Функции для логирования и анализа SQL-запросов.

```php
query_log(string $sql, array $bindings = [], float $time = 0.0, int $rows = 0): void
// Залогировать SQL запрос

query_dump(): void
// Вывести все SQL запросы

query_stats(): array
// Получить статистику запросов

query_slow(): array
// Получить медленные запросы

query_duplicates(): array
// Получить дублирующиеся запросы

query_clear(): void
// Очистить логи запросов

query_measure(callable $callback, ?string $label = null): mixed
// Измерить выполнение запроса
```

**Примеры:**
```php
// Ручное логирование (обычно делает Database класс автоматически)
query_log('SELECT * FROM users WHERE id = ?', [1], 2.5, 1);

// Анализ запросов
$stats = query_stats();
echo "Total queries: {$stats['count']}\n";
echo "Total time: {$stats['total_time']}ms\n";

// Поиск проблем
$slow = query_slow(); // Запросы > 100ms
$duplicates = query_duplicates(); // Повторяющиеся запросы

// Профилирование блока запросов
query_measure(function() {
    // Выполнение запросов
}, 'User Queries');
```

---

### 🎯 context.php - Контексты отладки

Функции для организации отладочной информации по контекстам.

```php
context_start(string $name, ?array $config = null): void
// Начать debug контекст

context_end(?string $name = null): void
// Закончить контекст

context_run(string $name, callable $callback, ?array $config = null): mixed
// Выполнить код в контексте

context_dump(?array $contexts = null): void
// Вывести информацию о контекстах

context_clear(?string $name = null): void
// Очистить контекст(ы)

context_current(): ?string
// Получить текущий контекст

context_filter(array $contexts): void
// Включить фильтрацию по контекстам
```

**Примеры:**
```php
// Ручное управление контекстом
context_start('auth');
// ... отладочные вызовы ...
context_end('auth');

// Автоматический контекст (рекомендуется)
context_run('payment', function() {
    dump($order);
    dump($transaction);
    // Всё внутри будет помечено контекстом 'payment'
});

// Фильтрация вывода
context_filter(['auth', 'payment']); // Показать только эти контексты
dump($data); // Не будет показан, если вне указанных контекстов
```

---

## Порядок загрузки

Группы хелперов загружаются в `core/bootstrap.php`.

### Способ 1: Загрузить все группы автоматически ⭐

```php
// Самый простой способ - загружает все доступные группы
\Core\HelperLoader::loadAllHelpers();
```

### Способ 2: Выборочная загрузка

```php
// Загрузить только нужные группы
\Core\HelperLoader::loadHelperGroups([
    'app',          // Базовые функции
    'environment',  // Определение окружения
    'debug',        // Отладочные функции
    'profiler',     // Профилирование
    'database',     // Отладка БД
    'context',      // Контексты
]);
```

**Рекомендуемый порядок загрузки:**
1. **app** - базовые функции (нужны везде)
2. **environment** - определение окружения (нужно для debug)
3. **debug** - основные отладочные функции
4. **profiler** - профилирование
5. **database** - отладка БД
6. **context** - контексты (опционально)

## Добавление своих хелперов

### Вариант 1: Добавить в существующую группу

Создайте файл в соответствующей папке группы:

```php
// core/helpers/app/my_helper.php
<?php declare(strict_types=1);

if (!function_exists('my_function')) {
    function my_function(): string
    {
        return 'Hello!';
    }
}
```

### Вариант 2: Создать новую группу

1. Создайте папку `core/helpers/my_group/`
2. Добавьте файлы с функциями в эту папку
3. Добавьте группу в `core/bootstrap.php`:

```php
\Core\HelperLoader::loadHelperGroups([
    'app',
    'environment',
    'debug',
    'profiler',
    'database',
    'context',
    'my_group',  // Ваша группа
]);
```

## Best Practices

### ✅ Хорошо
```php
// Используйте в разработке
if (is_dev()) {
    dump($data);
}

// Группируйте связанные дебаг-вызовы
context_run('api', function() {
    dump($request);
    dump($response);
});

// Профилируйте узкие места
$result = benchmark(fn() => heavyOperation(), 'Heavy Op');
```

### ❌ Плохо
```php
// Не оставляйте dd() в продакшене
dd($user); // Остановит выполнение!

// Не делайте dump в циклах
foreach ($items as $item) {
    dump($item); // Замедлит выполнение
}

// Используйте collect() вместо этого
foreach ($items as $item) {
    collect($item);
}
dump_all();
```

## Производительность

- Все debug-функции практически не влияют на производительность в production (если `is_debug() === false`)
- Timer и memory функции имеют минимальный overhead (~0.001ms)
- QueryDebugger автоматически отключается в production

## Дополнительные возможности

### Загрузка отдельной группы

```php
// Загрузить одну группу по требованию
\Core\HelperLoader::loadHelperGroup('profiler');
```

### Проверка загруженных групп

```php
$loader = \Core\HelperLoader::getInstance();

// Список загруженных
$loaded = $loader->getLoaded();

// Список доступных
$available = $loader->getAvailableGroups();

// Проверка конкретной группы
if ($loader->isLoaded('group:profiler')) {
    echo "Profiler загружен";
}
```

## См. также

- [HelperLoader API](HelperLoaderAPI.md) - Полная документация API загрузчика
- [Helper Loading Flow](HelperLoadingFlow.md) - Как работает загрузка
- [Debug Architecture](DebugArchitecture.md)
- [Debug Toolbar](DebugToolbar.md)
- [Query Debugger](QueryDebugger.md)
- [Memory Profiler](MemoryProfiler.md)
- [Debug Timer](DebugTimer.md)

