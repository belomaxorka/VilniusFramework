# Интеграция базы данных с Debug Toolbar

## Обзор

Полная интеграция `DatabaseManager` с `Debug Toolbar` для отслеживания всех SQL запросов в реальном времени.

## Возможности

- ✅ **Автоматическое логирование** всех SQL запросов
- ✅ **Отображение в Debug Toolbar** с подсветкой синтаксиса
- ✅ **Обнаружение медленных запросов** (> 100ms по умолчанию)
- ✅ **Поиск дубликатов** (N+1 проблемы)
- ✅ **Детальная статистика** (время, строки, bindings)
- ✅ **Caller tracking** - видно, откуда был вызван запрос

## Архитектура

```
DatabaseManager
    ↓ logQuery()
    ↓
QueryDebugger
    ↓ getQueries()
    ↓
QueriesCollector (Debug Toolbar)
    ↓
Отображение в UI
```

### Компоненты

1. **DatabaseManager** (`core/Database/DatabaseManager.php`)
   - Выполняет SQL запросы
   - Логирует через `logQuery()`
   - Передает данные в `QueryDebugger`

2. **QueryDebugger** (`core/QueryDebugger.php`)
   - Централизованное хранилище запросов
   - Анализирует медленные запросы
   - Обнаруживает дубликаты

3. **QueriesCollector** (`core/DebugToolbar/Collectors/QueriesCollector.php`)
   - Собирает данные из `QueryDebugger`
   - Форматирует для отображения
   - Отображает в Debug Toolbar

## Конфигурация

### config/database.php

```php
return [
    // ...
    
    /**
     * Логирование SQL запросов
     * В debug режиме всегда включено для Debug Toolbar
     */
    'log_queries' => env('DB_LOG_QUERIES', true),

    /**
     * Порог медленных запросов (в миллисекундах)
     */
    'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 100),
];
```

### .env

```env
# Database logging
DB_LOG_QUERIES=true
DB_SLOW_QUERY_THRESHOLD=100
```

## Что отображается

### В Header Toolbar

```
🗄️ 5 queries (1 slow)
```

- Общее количество запросов
- Количество медленных запросов (если есть)

### В панели Queries

#### Статистика

```
Total: 5 queries    Time: 245.50ms    Avg: 49.10ms
⚠ Slow: 1 queries   ⚠ Duplicates: 0   Rows: 150 total
```

#### Список запросов

Для каждого запроса:

1. **Номер запроса** - #1, #2, #3...
2. **Время выполнения** - с цветовой индикацией
   - Зеленый: быстрый запрос
   - Красный: медленный запрос (> порога)
3. **Количество строк** - сколько вернул запрос
4. **SQL запрос** - с подсветкой синтаксиса
5. **Bindings** - параметры запроса
6. **Caller** - файл и строка, откуда вызван запрос

### Пример отображения

```
┌──────────────────────────────────────────────────┐
│ #1                          12.45ms    100 rows  │
│ SELECT * FROM users WHERE active = ?             │
│ Bindings: [1]                                    │
│ 📍 HomeController.php:52                         │
└──────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────┐
│ #2                          ⚠ 150.30ms  5 rows   │
│ SELECT * FROM posts WHERE user_id = ?            │
│ Bindings: [1]                                    │
│ 📍 PostRepository.php:25                         │
└──────────────────────────────────────────────────┘
```

## Как это работает

### 1. Выполнение запроса

```php
// В HomeController
$users = $this->db->table('users')->get();
```

### 2. DatabaseManager логирует

```php
protected function run(string $query, array $bindings, callable $callback)
{
    $start = microtime(true);
    $result = $callback($query, $bindings);
    $time = microtime(true) - $start;
    
    // Определяем количество строк
    $rows = is_array($result) ? count($result) : 0;
    
    // Логируем
    $this->logQuery($query, $bindings, $time, null, $rows);
    
    return $result;
}
```

### 3. logQuery передает в QueryDebugger

```php
protected function logQuery(/* ... */)
{
    // Внутреннее логирование
    if ($this->loggingQueries) {
        $this->queryLog[] = [/* ... */];
    }

    // Интеграция с QueryDebugger для Debug Toolbar
    if (class_exists('\Core\QueryDebugger')) {
        \Core\QueryDebugger::log(
            $query,
            $bindings,
            $timeMs,
            $rows
        );
    }
}
```

### 4. QueriesCollector собирает данные

```php
public function collect(): void
{
    $this->data['queries'] = QueryDebugger::getQueries();
    $this->data['stats'] = QueryDebugger::getStats();
}
```

### 5. Отображение в UI

Debug Toolbar автоматически рендерит вкладку "Queries" с собранными данными.

## Подсветка синтаксиса

SQL ключевые слова подсвечиваются синим:

- `SELECT`, `FROM`, `WHERE`, `JOIN`
- `INSERT`, `UPDATE`, `DELETE`
- `ORDER BY`, `GROUP BY`, `LIMIT`
- И т.д.

## Медленные запросы

Запросы, превышающие порог (`slow_query_threshold`), выделяются:

- ❌ Красный фон
- ⚠ Иконка предупреждения
- Отображаются в статистике

### Пример

```php
// Этот запрос займет > 100ms и будет помечен как медленный
$users = $this->db->select('
    SELECT u.*, COUNT(p.id) as posts_count 
    FROM users u 
    LEFT JOIN posts p ON u.id = p.user_id 
    WHERE u.created_at > ? 
    GROUP BY u.id
', [date('Y-m-d', strtotime('-1 year'))]);
```

## Обнаружение дубликатов (N+1)

QueryDebugger автоматически обнаруживает повторяющиеся запросы:

```php
// BAD: N+1 проблема
$users = $this->db->table('users')->get();

foreach ($users as $user) {
    // Этот запрос выполнится N раз!
    $posts = $this->db->table('posts')
        ->where('user_id', $user['id'])
        ->get();
}
```

В Debug Toolbar вы увидите:
```
⚠ Duplicates: 10 queries
```

## Отключение в Production

В production логирование автоматически отключается через `Environment::isDebug()`:

```php
// В QueryDebugger::log()
if (!Environment::isDebug() || !self::$enabled) {
    return;
}
```

## Программное управление

### Включить/выключить логирование

```php
// Через DatabaseManager
$db->enableQueryLog();
$db->disableQueryLog();

// Через QueryDebugger
QueryDebugger::enable(false);
```

### Получить запросы программно

```php
// Все запросы
$queries = QueryDebugger::getQueries();

// Только медленные
$slowQueries = QueryDebugger::getSlowQueries();

// Дубликаты
$duplicates = QueryDebugger::getDuplicates();

// Статистика
$stats = QueryDebugger::getStats();
```

### Изменить порог медленных запросов

```php
// В runtime
QueryDebugger::setSlowQueryThreshold(200); // 200ms

// Или через конфиг
// config/database.php
'slow_query_threshold' => 200,
```

## Производительность

- Логирование работает только в debug режиме
- Минимальный overhead (< 1ms на запрос)
- Не влияет на production

## Примеры использования

### 1. Оптимизация запросов

```php
// До оптимизации - 5 запросов
$users = $this->db->table('users')->get();
foreach ($users as $user) {
    $posts = $this->db->table('posts')
        ->where('user_id', $user['id'])
        ->get();
}

// Debug Toolbar показывает: 6 queries (5 duplicates)

// После оптимизации - 1 запрос
$users = $this->db->table('users')
    ->select('u.*, COUNT(p.id) as posts_count')
    ->leftJoin('posts as p', 'u.id', '=', 'p.user_id')
    ->groupBy('u.id')
    ->get();

// Debug Toolbar показывает: 1 query
```

### 2. Поиск медленных участков

```php
// Открываем страницу с проблемой
// Смотрим в Debug Toolbar -> Queries
// Находим медленные запросы (красные)
// Видим caller - где вызван запрос
// Оптимизируем этот участок кода
```

### 3. Анализ bindings

```php
// В Debug Toolbar видим:
// SELECT * FROM users WHERE email = ?
// Bindings: ["user@example.com"]

// Можем проверить, правильные ли параметры передаются
```

## Troubleshooting

### Не отображаются запросы

**Проблема**: Вкладка "Queries" пустая

**Решение**:
1. Убедитесь, что `APP_DEBUG=true` в `.env`
2. Проверьте `DB_LOG_QUERIES=true` в `.env`
3. Убедитесь, что запросы действительно выполняются

### Не работает подсветка синтаксиса

**Проблема**: SQL отображается без цветов

**Решение**: Подсветка работает. Проверьте, что стили не переопределены.

### Медленные запросы не выделяются

**Проблема**: Все запросы зеленые, хотя некоторые медленные

**Решение**: Проверьте `DB_SLOW_QUERY_THRESHOLD` в конфигурации. Возможно, порог слишком высокий.

## Заключение

Интеграция DatabaseManager с Debug Toolbar предоставляет:

- ✅ Полную прозрачность SQL запросов
- ✅ Простоту отладки
- ✅ Инструменты для оптимизации
- ✅ Обнаружение проблем производительности

Используйте Debug Toolbar для создания быстрых и эффективных приложений! 🚀

