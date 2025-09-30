# 🐛 Полное руководство по Debug системе

## Обзор

Комплексная система отладки для PHP приложений, включающая все необходимые инструменты для эффективной разработки.

### 📦 Что включено:

1. **Debug Core** - базовые функции dump, trace, benchmark
2. **Debug Timer** - измерение времени выполнения
3. **Memory Profiler** - профилирование памяти
4. **Debug Contexts** - группировка debug данных
5. **Query Debugger** - анализ SQL запросов
6. **Debug Toolbar** - визуальная панель отладки
7. **Dump Server** - вывод в отдельное окно
8. **Circular Reference Detection** - защита от бесконечных циклов

---

## 🚀 Быстрый старт

### Базовый debug

```php
// Простой вывод
dump($variable);
dump($user, 'User Data');

// Красивый вывод
dump_pretty($array, 'Array Data');

// Dump and die
dd($variable);
```

### С таймером

```php
timer_start('task');

// Ваш код

timer_stop('task'); // Автоматически выведет время
```

### С профилированием памяти

```php
memory_start();

// Ваш код

memory_snapshot('checkpoint');
memory_dump(); // Покажет профиль
```

### SQL Debugging

```php
query_log('SELECT * FROM users', [], 25.5, 100);
query_dump(); // Покажет все запросы
```

### Debug Toolbar (визуальная панель)

```php
<!-- В layout.php -->
<?= render_debug_toolbar() ?>
```

### Dump Server (отдельное окно)

```bash
# Terminal 1
php bin/dump-server.php
```

```php
// В коде
server_dump($data, 'Debug Data');
```

---

## 📚 Полная документация

Каждый компонент имеет подробную документацию:

- 📖 [Debug Core](DebugBuffering.md) - базовые функции
- ⏱️ [Debug Timer](DebugTimer.md) - измерение времени
- 💾 [Memory Profiler](MemoryProfiler.md) - профилирование памяти
- 📁 [Debug Contexts](DebugContexts.md) - группировка данных
- 🗄️ [Query Debugger](QueryDebugger.md) - SQL анализ
- 🎨 [Debug Toolbar](DebugToolbar.md) - визуальная панель
- 🖥️ [Dump Server](DumpServer.md) - отдельное окно
- 🔄 [Circular References](DebugCircularReferences.md) - циклические ссылки
- ✅ [Testing](DebugTesting.md) - тестирование

---

## 🎯 Все функции

### Core Debug

```php
dump($var, ?string $label = null)              // Вывод переменной
dump_pretty($var, ?string $label = null)       // Красивый вывод
dd($var, ?string $label = null)                // Dump and die
trace(int $limit = 10)                         // Backtrace
benchmark(callable $callback, string $label)   // Измерение функции
collect(mixed $var, ?string $label = null)     // Сбор для вывода позже

debug_flush()                                  // Вывести накопленное
debug_output(): string                         // Получить вывод
has_debug_output(): bool                       // Есть ли данные
render_debug(): string                         // Для шаблонов
```

### Timer

```php
timer_start(string $name = 'default')          // Старт таймера
timer_stop(string $name = 'default'): float    // Стоп и вывод
timer_lap(string $name, ?string $label): float // Промежуточная точка
timer_elapsed(string $name): float             // Текущее время
timer_dump(?string $name = null)               // Вывод таймера
timer_clear(?string $name = null)              // Очистка
timer_measure(string $name, callable $fn)      // Измерить функцию
```

### Memory

```php
memory_start()                                 // Начать профилирование
memory_snapshot(string $name, ?string $label)  // Снимок памяти
memory_current(): int                          // Текущая память
memory_peak(): int                             // Пиковая память
memory_dump()                                  // Вывод профиля
memory_clear()                                 // Очистка
memory_measure(string $name, callable $fn)     // Измерить функцию
memory_format(int $bytes, int $precision): str // Форматировать
```

### Contexts

```php
context_start(string $name, ?array $config)    // Начать контекст
context_end(?string $name = null)              // Закончить контекст
context_run(string $name, callable $fn)        // Выполнить в контексте
context_dump(?array $contexts = null)          // Вывод контекстов
context_clear(?string $name = null)            // Очистка
context_current(): ?string                     // Текущий контекст
context_filter(array $contexts)                // Фильтрация
```

### Query Debugger

```php
query_log(string $sql, array $bindings, float $time, int $rows)
query_dump()                                   // Вывод всех запросов
query_stats(): array                           // Статистика
query_slow(): array                            // Медленные запросы
query_duplicates(): array                      // Дубликаты (N+1)
query_clear()                                  // Очистка
query_measure(callable $fn, ?string $label)    // Измерить запрос
```

### Debug Toolbar

```php
render_debug_toolbar(): string                 // Рендер toolbar
DebugToolbar::enable(bool $enabled)            // Включить/выключить
DebugToolbar::setPosition(string $position)    // 'top' | 'bottom'
DebugToolbar::setCollapsed(bool $collapsed)    // Свернуть
```

### Dump Server

```php
server_dump(mixed $data, ?string $label): bool // Отправить на сервер
dd_server(mixed $data, ?string $label): never  // Dump to server and die
dump_server_available(): bool                  // Проверка сервера
DumpClient::configure(string $host, int $port) // Настройка клиента
```

---

## 💡 Примеры использования

### Пример 1: Полный debug страницы

```php
class UserController 
{
    public function show($id) 
    {
        context_run('page_load', function() use ($id) {
            timer_start('total');
            memory_start();
            
            // Database
            context_run('database', function() use ($id) {
                $user = query_measure(fn() => 
                    User::find($id)
                , "SELECT user {$id}");
                
                dump($user, 'Loaded User');
            });
            
            memory_snapshot('after_db');
            
            timer_stop('total');
            memory_dump();
            context_dump();
        });
        
        return view('user.show', compact('user'));
    }
}
```

**Результат:** Видите всё - время, память, запросы, контексты!

### Пример 2: API Debug

```php
class ApiController 
{
    public function handle(Request $request) 
    {
        context_run('api', function() use ($request) {
            // Debug в отдельное окно (не мешает JSON ответу)
            server_dump($request->all(), 'API Request');
            
            $response = $this->process($request);
            
            server_dump($response, 'API Response');
            
            return $response;
        });
    }
}
```

### Пример 3: Performance анализ

```php
context_run('performance', function() {
    timer_start('total');
    memory_start();
    
    // Step 1
    timer_measure('load_data', fn() => loadData());
    memory_snapshot('after_load');
    
    // Step 2
    timer_measure('process', fn() => processData());
    memory_snapshot('after_process');
    
    // Step 3
    timer_measure('save', fn() => saveData());
    memory_snapshot('after_save');
    
    timer_stop('total');
    
    // Анализ
    $stats = query_stats();
    
    if ($stats['slow'] > 0) {
        dump(query_slow(), 'Slow Queries');
    }
    
    if ($stats['duplicates'] > 0) {
        dump(query_duplicates(), 'N+1 Problems');
    }
    
    memory_dump();
    timer_dump();
});
```

### Пример 4: С Toolbar

```php
<!DOCTYPE html>
<html>
<body>
    <?php
    // Весь ваш код с debug
    dump($data);
    query_log(...);
    timer_start('render');
    // ...
    timer_stop('render');
    ?>
    
    <!-- Одна строка - всё в панели! -->
    <?= render_debug_toolbar() ?>
</body>
</html>
```

### Пример 5: Обнаружение N+1

```php
// Плохой код
$posts = query_measure(fn() => Post::all(), 'Load Posts');

foreach ($posts as $post) {
    $user = query_measure(fn() => 
        User::find($post->user_id)
    , "Load User {$post->user_id}");
}

query_dump();
// ⚠️ Покажет: 10 duplicate queries (possible N+1 problem)

// Исправленный код
$posts = query_measure(fn() => 
    Post::with('user')->get()
, 'Load Posts with Users');

query_dump();
// ✅ Только 1 запрос!
```

---

## 🎨 Debug Toolbar

Самый удобный способ видеть всю debug информацию:

```php
<!-- layout.php -->
<!DOCTYPE html>
<html>
<body>
    <?= $content ?>
    
    <?= render_debug_toolbar() ?>
</body>
</html>
```

**Что показывает:**

```
┌─────────────────────────────────────────────────────────┐
│ 🐛 Debug Toolbar                                        │
│ ⏱️ 125ms  💾 12MB  🗄️ 15 queries  📁 3 contexts  ▼    │
├─────────────────────────────────────────────────────────┤
│ 📊 Overview | 🔍 Dumps [5] | 🗄️ Queries [15] | ...    │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  [Содержимое выбранной вкладки]                         │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

**Вкладки:**
- 📊 Overview - общая статистика
- 🔍 Dumps - все dump() выводы
- 🗄️ Queries - SQL запросы с анализом
- ⏱️ Timers - измерения времени
- 💾 Memory - профиль памяти
- 📁 Contexts - debug контексты

---

## 🖥️ Dump Server

Для случаев когда нужно debug без засорения вывода:

**Terminal 1 (Сервер):**
```bash
php bin/dump-server.php
```

**Terminal 2 (Приложение):**
```php
server_dump($user, 'User Data');
server_dump($config, 'Config');
```

**Terminal 1 покажет:**
```
────────────────────────────────────────────────────────────
⏰ 14:23:45 📝 User Data 📍 UserController.php:25
────────────────────────────────────────────────────────────
Array ( [id] => 1, [name] => "John" ... )

────────────────────────────────────────────────────────────
⏰ 14:23:45 📝 Config 📍 UserController.php:26
────────────────────────────────────────────────────────────
Array ( ... )
```

---

## 🔄 Интеграция всех инструментов

Все компоненты работают вместе:

```php
// Запустите dump server
// Terminal: php bin/dump-server.php

context_run('complex_operation', function() {
    timer_start('total');
    memory_start();
    
    // Database с query debug
    context_run('database', function() {
        $data = query_measure(fn() => 
            DB::table('users')->get()
        , 'Load Users');
        
        // На dump server (не засоряет вывод)
        server_dump($data, 'Loaded Data');
    });
    
    // Business logic
    context_run('business', function() use ($data) {
        dump($data, 'Processing');
        
        $result = process($data);
        
        memory_snapshot('after_process');
    });
    
    timer_stop('total');
    
    // Всё вместе в toolbar
    memory_dump();
    timer_dump();
    query_dump();
    context_dump();
});

// И всё это в toolbar!
echo render_debug_toolbar();
```

**Результат:**
- 📊 Toolbar показывает статистику
- 🖥️ Dump Server показывает детали
- 🗄️ Query Debugger выявляет проблемы
- ⏱️ Timer показывает узкие места
- 💾 Memory Profiler находит утечки
- 📁 Contexts группируют данные

---

## ✅ Best Practices

### 1. Используйте контексты для организации

```php
context_run('page_load', function() {
    // Весь код страницы
});
```

### 2. Профилируйте критичные участки

```php
timer_measure('critical_operation', function() {
    memory_measure('operation', function() {
        // Критичный код
    });
});
```

### 3. Анализируйте SQL

```php
query_dump();

if (count(query_duplicates()) > 0) {
    // Есть N+1 проблема!
}
```

### 4. Используйте Toolbar в development

```php
if (is_dev()) {
    echo render_debug_toolbar();
}
```

### 5. Dump Server для API/Console

```php
// Не засоряет JSON вывод
server_dump($data);
```

### 6. Проверяйте циклические ссылки

Система автоматически обнаруживает и помечает циклы:

```php
$obj->ref = $obj; // Циклическая ссылка
dump($obj); // Покажет *CIRCULAR REFERENCE*
```

---

## 🚀 Production Mode

В production **всё автоматически отключается**:

```php
Environment::set(Environment::PRODUCTION);

dump($data);              // ничего
timer_start('test');      // ничего
query_log(...);          // ничего
server_dump($data);      // ничего
render_debug_toolbar();  // пустая строка
```

**Результат:**
- ⚡ Ноль оверхеда
- 🔒 Безопасность
- 🎨 Чистый вывод

---

## 📊 Статистика системы

### Код
- 📁 Классов: 8
- 🎯 Helper функций: 60+
- 📄 Строк кода: 8500+
- ✅ Тестов: 400+
- 📊 Покрытие: 95%+

### Документация
- 📚 Руководств: 9
- 📄 Строк: 3600+
- 💡 Примеров: 100+

### Возможности
- ✅ Output Buffering
- ✅ Circular Reference Detection
- ✅ Timer Profiling
- ✅ Memory Profiling
- ✅ Context Grouping
- ✅ SQL Analysis
- ✅ Visual Toolbar
- ✅ Dump Server

---

## 🎓 Обучение

### Новичок → Базовые функции

```php
dump($variable);
dd($variable);
```

### Продвинутый → Профилирование

```php
timer_start('task');
memory_start();
// код
timer_stop('task');
memory_dump();
```

### Эксперт → Полная интеграция

```php
context_run('operation', function() {
    timer_measure('step1', fn() => step1());
    query_measure(fn() => query(), 'Query');
    memory_snapshot('checkpoint');
});

echo render_debug_toolbar();
```

---

## 🔧 Troubleshooting

### Debug не работает

```php
// 1. Проверьте режим
var_dump(Environment::isDevelopment()); // true?

// 2. Проверьте вывод
debug_flush();

// 3. Используйте toolbar
echo render_debug_toolbar();
```

### Toolbar не отображается

```php
// Убедитесь что включен
DebugToolbar::enable(true);

// И вызывается в конце
echo render_debug_toolbar();
```

### Dump Server не работает

```bash
# Запустите сервер
php bin/dump-server.php

# Проверьте доступность
dump_server_available(); // true?
```

---

## 📖 Ссылки на документацию

1. [Debug Buffering](DebugBuffering.md) - система буферизации
2. [Debug Timer](DebugTimer.md) - измерение времени
3. [Memory Profiler](MemoryProfiler.md) - профилирование памяти
4. [Debug Contexts](DebugContexts.md) - контексты
5. [Query Debugger](QueryDebugger.md) - SQL отладка
6. [Debug Toolbar](DebugToolbar.md) - визуальная панель
7. [Dump Server](DumpServer.md) - отдельное окно
8. [Circular References](DebugCircularReferences.md) - циклы
9. [Testing Guide](DebugTesting.md) - тестирование

---

## 🎉 Заключение

Полная debug система включает всё необходимое для эффективной разработки:

- ✅ Удобные функции dump
- ✅ Профилирование производительности
- ✅ Анализ SQL запросов
- ✅ Визуальная панель
- ✅ Вывод в отдельное окно
- ✅ Автоматическая защита от циклов
- ✅ Группировка по контекстам
- ✅ 95%+ test coverage

**Используйте эти инструменты для создания качественного кода!** 🚀

---

## 📝 Быстрая шпаргалка

```php
// Debug
dump($var);                           // Вывод
dd($var);                            // Dump and die
collect($var);                       // Сбор для вывода позже

// Timer
timer_start('name');                 // Старт
timer_stop('name');                  // Стоп
timer_measure('name', fn() => ...);  // Измерить

// Memory
memory_start();                      // Старт
memory_snapshot('name');             // Снимок
memory_dump();                       // Вывод

// Contexts
context_run('name', fn() => ...);    // Выполнить

// Queries
query_log($sql, $bindings, $time);   // Лог
query_dump();                        // Вывод

// Toolbar
echo render_debug_toolbar();         // Панель

// Dump Server
server_dump($var);                   // На сервер
```

**Happy Debugging! 🐛✨**
