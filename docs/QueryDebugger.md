# SQL Query Debugger - Логирование и анализ SQL запросов

## Обзор

SQL Query Debugger - инструмент для отслеживания, анализа и оптимизации SQL запросов в приложении.

### Возможности:
- 📊 **Логирование** - все SQL запросы с временем выполнения
- 🐌 **Slow Queries** - автоматическое обнаружение медленных запросов
- 🔄 **N+1 Detection** - поиск дублирующихся запросов
- 🎨 **Syntax Highlighting** - подсветка SQL синтаксиса
- 📈 **Statistics** - детальная статистика по запросам
- 📍 **Caller Tracking** - отслеживание источника запроса

## Быстрый старт

### Автоматическое логирование

```php
// В вашем коде базы данных
$start = microtime(true);
$result = $db->query('SELECT * FROM users WHERE active = 1');
$time = (microtime(true) - $start) * 1000;

query_log(
    'SELECT * FROM users WHERE active = 1',
    ['active' => 1],
    $time,
    $result->rowCount()
);

// В конце вывести все запросы
query_dump();
```

**Вывод:**
```
📊 SQL Query Debugger

Statistics:
Total Queries: 5    Slow Queries: 1    Duplicates: 0
Total Time: 125.45ms    Avg Time: 25.09ms    Total Rows: 350

Query Log:
┌────────────────────────────────────────────────────┐
│ #1                              12.45ms    100 rows│
│ SELECT * FROM users WHERE active = 1               │
│ Bindings: {"active": 1}                            │
│ 📍 UserController.php:25                           │
└────────────────────────────────────────────────────┘
```

### Measure (автоматический)

```php
$users = query_measure(function() use ($db) {
    return $db->query('SELECT * FROM users');
}, 'Load Users');

query_dump();
```

## API Reference

### Основные функции

#### query_log(string $sql, array $bindings = [], float $time = 0.0, int $rows = 0)
Логирует SQL запрос.

```php
query_log(
    'SELECT * FROM users WHERE id = ?',
    [1],              // bindings
    15.5,             // время в ms
    1                 // количество строк
);
```

#### query_dump()
Выводит все залогированные запросы.

```php
query_dump();
```

#### query_stats(): array
Получает статистику по запросам.

```php
$stats = query_stats();
// [
//     'total' => 10,
//     'slow' => 2,
//     'duplicates' => 1,
//     'total_time' => 125.5,
//     'avg_time' => 12.55,
//     'total_rows' => 500
// ]
```

#### query_slow(): array
Получает только медленные запросы.

```php
$slowQueries = query_slow();
```

#### query_duplicates(): array
Получает дублирующиеся запросы.

```php
$duplicates = query_duplicates();
// [
//     [
//         'query' => 'SELECT * FROM users WHERE id = ?',
//         'count' => 5,
//         'indices' => [2, 5, 7, 9, 11]
//     ]
// ]
```

#### query_clear()
Очищает логи запросов.

```php
query_clear();
```

#### query_measure(callable $callback, ?string $label = null): mixed
Измеряет время выполнения запроса.

```php
$result = query_measure(function() {
    return executeComplexQuery();
}, 'Complex Query');
```

## Настройка

### Порог медленных запросов

```php
use Core\QueryDebugger;

// Установить порог в 50ms (по умолчанию 100ms)
QueryDebugger::setSlowQueryThreshold(50.0);

// Теперь запросы >50ms считаются медленными
query_log('SLOW QUERY', [], 75.0);

$slow = query_slow();
// ['SLOW QUERY']
```

### Обнаружение дубликатов

```php
// Включить/выключить поиск дубликатов
QueryDebugger::setDetectDuplicates(true);  // по умолчанию
QueryDebugger::setDetectDuplicates(false); // отключить
```

### Включение/выключение

```php
// Отключить логирование
QueryDebugger::enable(false);

query_log('Not logged');

// Включить обратно
QueryDebugger::enable(true);

query_log('Logged');
```

## Класс QueryDebugger

Для прямого использования:

```php
use Core\QueryDebugger;

// Логирование
QueryDebugger::log($sql, $bindings, $time, $rows);

// Получение данных
$queries = QueryDebugger::getQueries();
$slow = QueryDebugger::getSlowQueries();
$duplicates = QueryDebugger::getDuplicates();
$stats = QueryDebugger::getStats();

// Настройки
QueryDebugger::setSlowQueryThreshold(50.0);
QueryDebugger::setDetectDuplicates(true);
QueryDebugger::enable(true);

// Measure
$result = QueryDebugger::measure($callback, $label);

// Вывод
QueryDebugger::dump();

// Очистка
QueryDebugger::clear();
```

## Интеграция с Database

### Пример интеграции с PDO

```php
class Database 
{
    private PDO $pdo;
    
    public function query(string $sql, array $bindings = []): PDOStatement 
    {
        $start = microtime(true);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        
        $time = (microtime(true) - $start) * 1000;
        
        // Логируем запрос
        query_log($sql, $bindings, $time, $stmt->rowCount());
        
        return $stmt;
    }
}
```

### Пример с QueryBuilder

```php
class QueryBuilder 
{
    public function get(): array 
    {
        $sql = $this->toSql();
        $bindings = $this->getBindings();
        
        return query_measure(function() use ($sql, $bindings) {
            return $this->connection->select($sql, $bindings);
        }, $sql);
    }
}
```

## Обнаружение проблем

### N+1 Problem Detection

```php
// Плохой код с N+1
$posts = query_measure(fn() => 
    $db->query('SELECT * FROM posts')
, 'Load Posts');

foreach ($posts as $post) {
    // N запросов в цикле!
    $user = query_measure(fn() => 
        $db->query("SELECT * FROM users WHERE id = {$post->user_id}")
    , "Load User {$post->user_id}");
}

query_dump();
// Покажет: ⚠️ 10 duplicate queries (possible N+1 problem)

$duplicates = query_duplicates();
// [
//     [
//         'query' => 'SELECT * FROM users WHERE id = ?',
//         'count' => 10
//     ]
// ]
```

**Решение:**
```php
// Хорошо: один запрос с JOIN
$posts = query_measure(fn() => 
    $db->query('SELECT posts.*, users.* FROM posts JOIN users ON posts.user_id = users.id')
, 'Load Posts with Users');

query_dump();
// Только 1 запрос!
```

### Slow Query Detection

```php
QueryDebugger::setSlowQueryThreshold(100.0);

// Медленный запрос
query_log('SELECT * FROM huge_table WHERE unindexed_column = ?', [1], 250.0);

query_dump();
// Покажет: ⚠️ 1 slow queries (>100ms)

$slow = query_slow();
foreach ($slow as $query) {
    echo "Slow: {$query['sql']} ({$query['time']}ms)\n";
    // Оптимизируйте!
}
```

## Примеры использования

### Пример 1: Анализ страницы

```php
class UserController 
{
    public function index() 
    {
        // Автоматическое логирование
        $users = $this->db->query('SELECT * FROM users');
        
        foreach ($users as $user) {
            $posts = $this->db->query("SELECT * FROM posts WHERE user_id = ?", [$user->id]);
        }
        
        // Вывод анализа
        query_dump();
        
        return view('users.index', ['users' => $users]);
    }
}
```

**Вывод покажет:**
- Общее количество запросов
- Дублирующиеся запросы (N+1)
- Время выполнения
- Рекомендации по оптимизации

### Пример 2: Профилирование API

```php
context_run('api', function() {
    timer_start('api_request');
    memory_start();
    
    // Database queries
    context_run('database', function() {
        $users = query_measure(fn() => 
            User::with('posts')->get()
        , 'Load users with posts');
        
        $stats = query_measure(fn() => 
            DB::raw('SELECT COUNT(*) FROM analytics')
        , 'Get analytics');
    });
    
    timer_stop('api_request');
    
    // Вывод всего
    memory_dump();
    timer_dump();
    query_dump();
    context_dump();
});
```

### Пример 3: Оптимизация запросов

```php
// До оптимизации
QueryDebugger::setSlowQueryThreshold(50.0);

query_log('SELECT * FROM orders WHERE created_at > ?', ['2024-01-01'], 150.0, 1000);

$stats = query_stats();
if ($stats['slow'] > 0) {
    echo "⚠️ Found {$stats['slow']} slow queries!\n";
    
    foreach (query_slow() as $query) {
        echo "- {$query['sql']}: {$query['time']}ms\n";
        // TODO: Add index!
    }
}

// После добавления индекса
query_log('SELECT * FROM orders WHERE created_at > ?', ['2024-01-01'], 15.0, 1000);
// ✅ Намного быстрее!
```

### Пример 4: Dashboard с метриками

```php
class DebugDashboard 
{
    public function show() 
    {
        $stats = query_stats();
        $slow = query_slow();
        $duplicates = query_duplicates();
        
        return view('debug.dashboard', [
            'total_queries' => $stats['total'],
            'slow_count' => $stats['slow'],
            'duplicate_count' => $stats['duplicates'],
            'total_time' => $stats['total_time'],
            'avg_time' => $stats['avg_time'],
            'slow_queries' => $slow,
            'duplicates' => $duplicates,
        ]);
    }
}
```

## Советы и Best Practices

### 1. Логируйте все запросы в development

```php
// В вашем Database wrapper
public function execute($sql, $bindings = []) {
    $start = microtime(true);
    $result = $this->pdo->execute($sql, $bindings);
    $time = (microtime(true) - $start) * 1000;
    
    // Всегда логируем в dev
    if (is_dev()) {
        query_log($sql, $bindings, $time, $result->rowCount());
    }
    
    return $result;
}
```

### 2. Установите разумный порог

```php
// Для веб-приложений
QueryDebugger::setSlowQueryThreshold(100.0); // 100ms

// Для API
QueryDebugger::setSlowQueryThreshold(50.0);  // 50ms

// Для real-time
QueryDebugger::setSlowQueryThreshold(10.0);  // 10ms
```

### 3. Используйте с контекстами

```php
context_run('database', function() {
    query_log('SELECT * FROM users');
    query_log('SELECT * FROM posts');
});

context_dump(); // Группирует SQL запросы
```

### 4. Анализируйте дубликаты

```php
$duplicates = query_duplicates();

if (count($duplicates) > 0) {
    echo "⚠️ Possible N+1 problems:\n";
    foreach ($duplicates as $dup) {
        echo "- {$dup['query']} (executed {$dup['count']} times)\n";
    }
}
```

### 5. Очищайте логи периодически

```php
// В начале каждого запроса
query_clear();

// Ваш код с запросами
handleRequest();

// Вывод результатов
query_dump();
```

## Production Mode

В production режиме Query Debugger **отключен**:

```php
// В production
query_log('SELECT * FROM users', [], 10.0); // ничего не делает
query_dump(); // ничего не выводит

// Но query_measure все равно выполняет запрос
$result = query_measure(fn() => doQuery()); // работает
```

Это обеспечивает:
- ⚡ Ноль оверхеда в production
- 🔒 Безопасность (не раскрывает SQL)
- 📊 Возможность измерений в dev

## Troubleshooting

### Запросы не логируются

**Проблема:** `query_log()` не работает

**Решение:**
```php
// 1. Проверьте режим
var_dump(Environment::isDevelopment()); // true?

// 2. Проверьте что включено
QueryDebugger::enable(true);

// 3. Вызовите dump
query_dump();
debug_flush();
```

### Много ложных дубликатов

**Проблема:** Показывает дубликаты, которые не дубликаты

**Решение:**
```php
// Дубликаты определяются по паттерну, не точному совпадению
// Это нормально для:
// - Разные ID: "WHERE id = 1" и "WHERE id = 2"
// - Разные значения: "WHERE name = 'John'" и "WHERE name = 'Jane'"

// Если это мешает, отключите:
QueryDebugger::setDetectDuplicates(false);
```

### Неточное время выполнения

**Проблема:** Время кажется неправильным

**Решение:**
```php
// Убедитесь что передаете время в миллисекундах
$start = microtime(true);
$result = $db->query($sql);
$time = (microtime(true) - $start) * 1000; // ← * 1000 для ms

query_log($sql, [], $time);
```

## FAQ

**Q: Как интегрировать с моей ORM?**

A: Добавьте логирование в методы выполнения запросов:
```php
class MyORM {
    protected function executeQuery($sql, $bindings) {
        $start = microtime(true);
        $result = $this->connection->query($sql, $bindings);
        $time = (microtime(true) - $start) * 1000;
        
        query_log($sql, $bindings, $time, count($result));
        
        return $result;
    }
}
```

**Q: Что такое N+1 проблема?**

A: Когда делается 1 запрос + N запросов в цикле:
```php
// 1 запрос
$posts = $db->query('SELECT * FROM posts'); 

// + N запросов
foreach ($posts as $post) {
    $user = $db->query("SELECT * FROM users WHERE id = {$post->user_id}");
}
```

Решение: использовать JOIN или eager loading.

**Q: Сколько запросов нормально для страницы?**

A: 
- 📗 1-5 запросов - отлично
- 📘 5-10 запросов - нормально
- 📙 10-20 запросов - много, но приемлемо
- 📕 >20 запросов - нужна оптимизация!

**Q: Можно ли использовать в production?**

A: Технически да, но не рекомендуется. Используйте в dev/staging для оптимизации, затем отключайте в prod.

**Q: Как найти источник запроса?**

A: Query Debugger автоматически отслеживает caller:
```php
$queries = QueryDebugger::getQueries();
foreach ($queries as $q) {
    echo "{$q['caller']['file']}:{$q['caller']['line']}\n";
}
```

## Заключение

SQL Query Debugger - незаменимый инструмент для:

- ✅ Обнаружения медленных запросов
- ✅ Поиска N+1 проблем
- ✅ Оптимизации производительности БД
- ✅ Анализа использования БД
- ✅ Улучшения архитектуры запросов

Используйте его для создания быстрых и эффективных приложений! 📊🚀
