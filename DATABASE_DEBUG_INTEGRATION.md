# Интеграция Database с Debug Toolbar - Резюме изменений

## Дата: 2025-10-03

## 🎯 Цель

Полная интеграция `DatabaseManager` с `Debug Toolbar` для отслеживания и анализа всех SQL запросов в реальном времени.

## ✅ Что было сделано

### 1. Обновлен DatabaseManager (`core/Database/DatabaseManager.php`)

#### Изменения в конструкторе

```php
public function __construct(array $config)
{
    // ...
    
    // Настраиваем QueryDebugger для Debug Toolbar
    if (class_exists('\Core\QueryDebugger')) {
        if (isset($config['slow_query_threshold'])) {
            \Core\QueryDebugger::setSlowQueryThreshold((float)$config['slow_query_threshold']);
        }
    }
}
```

#### Обновлен метод `run()`

Теперь определяет количество затронутых строк и передает их в `logQuery()`:

```php
protected function run(string $query, array $bindings, callable $callback)
{
    $start = microtime(true);
    $result = $callback($query, $bindings);
    $time = microtime(true) - $start;

    // Определяем количество затронутых строк
    $rows = 0;
    if (is_array($result)) {
        $rows = count($result);
    } elseif (is_int($result)) {
        $rows = $result;
    }

    // Логируем успешный запрос
    $this->logQuery($query, $bindings, $time, null, $rows);

    return $result;
}
```

#### Обновлен метод `logQuery()`

Добавлена интеграция с `QueryDebugger`:

```php
protected function logQuery(string $query, array $bindings, float $time, ?string $error = null, int $rows = 0): void
{
    $timeMs = round($time * 1000, 2);
    
    // Внутреннее логирование
    if ($this->loggingQueries) {
        $this->queryLog[] = [
            'query' => $query,
            'bindings' => $bindings,
            'time' => $timeMs,
            'error' => $error,
            'rows' => $rows,  // ← Теперь сохраняется количество строк
            'timestamp' => date('Y-m-d H:i:s')
        ];
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

### 2. Обновлена конфигурация (`config/database.php`)

Добавлены новые параметры:

```php
return [
    'default' => env('DB_CONNECTION', 'sqlite'),

    /**
     * Логирование SQL запросов
     * В debug режиме всегда включено для Debug Toolbar
     */
    'log_queries' => env('DB_LOG_QUERIES', true),

    /**
     * Порог медленных запросов (в миллисекундах)
     */
    'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 100),

    'connections' => [
        // ...
    ]
];
```

### 3. Улучшен QueriesCollector (`core/DebugToolbar/Collectors/QueriesCollector.php`)

#### Обновлен метод `render()`

Добавлено:
- ✅ Блок со статистикой (total, time, avg, slow, duplicates, rows)
- ✅ Отображение bindings для каждого запроса
- ✅ Информация о caller (файл и строка)
- ✅ Подсветка SQL синтаксиса

#### Добавлен метод `highlightSql()`

```php
private function highlightSql(string $sql): string
{
    $keywords = ['SELECT', 'FROM', 'WHERE', 'JOIN', ...];
    
    // Подсветка ключевых слов синим цветом
    foreach ($keywords as $keyword) {
        $highlighted = preg_replace(
            '/\b(' . $keyword . ')\b/i',
            '<span style="color: #0066cc; font-weight: bold;">$1</span>',
            $highlighted
        );
    }
    
    return $highlighted;
}
```

## 📊 Что теперь отображается в Debug Toolbar

### В Header

```
🗄️ 5 queries (1 slow)
```

### В панели Queries

```
┌─────────────────────────────────────────────────────────┐
│ Статистика                                              │
├─────────────────────────────────────────────────────────┤
│ Total: 5 queries    Time: 245.50ms    Avg: 49.10ms     │
│ ⚠ Slow: 1 queries   ⚠ Duplicates: 0   Rows: 150 total  │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ #1                                 12.45ms    100 rows  │
│ SELECT * FROM users WHERE active = ?                    │
│ Bindings: [1]                                           │
│ 📍 HomeController.php:52                                │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ #2                          ⚠ 150.30ms    5 rows        │
│ SELECT * FROM posts WHERE user_id = ?                   │
│ Bindings: [1]                                           │
│ 📍 PostRepository.php:25                                │
└─────────────────────────────────────────────────────────┘
```

## 🔄 Поток данных

```
1. HomeController
   ↓ $this->db->table('users')->get()
   
2. DatabaseManager::run()
   ↓ выполняет запрос
   ↓ считает время и количество строк
   ↓ logQuery($query, $bindings, $time, null, $rows)
   
3. DatabaseManager::logQuery()
   ↓ сохраняет в $this->queryLog (внутренний лог)
   ↓ QueryDebugger::log($query, $bindings, $time, $rows)
   
4. QueryDebugger
   ↓ сохраняет в static $queries
   ↓ анализирует (медленные, дубликаты)
   ↓ getQueries(), getStats()
   
5. QueriesCollector::collect()
   ↓ $this->data['queries'] = QueryDebugger::getQueries()
   ↓ $this->data['stats'] = QueryDebugger::getStats()
   
6. QueriesCollector::render()
   ↓ форматирует HTML с подсветкой
   ↓ отображает в Debug Toolbar
```

## 📝 Файлы изменений

```
core/
├── Database/
│   └── DatabaseManager.php          ← Обновлен (logQuery, run, __construct)
└── DebugToolbar/
    └── Collectors/
        └── QueriesCollector.php     ← Улучшен (render, highlightSql)

config/
└── database.php                      ← Обновлен (log_queries, slow_query_threshold)

docs/
└── DatabaseDebugToolbarIntegration.md   ← Создан (полная документация)
```

## 🎨 Визуальные улучшения

### Медленные запросы

- ❌ **Красный фон** для запросов > порога
- ⚠ **Иконка предупреждения** в статистике
- 🔴 **Красный цвет времени**

### Быстрые запросы

- ✅ **Белый фон**
- 🟢 **Зеленый цвет времени**
- ✔ **Нормальное отображение**

### Подсветка SQL

- 🔵 **Синие ключевые слова** (SELECT, FROM, WHERE...)
- ⚫ **Черный текст** для остального

## 🔧 Настройка

### .env файл

Добавить (опционально):

```env
# Database Query Logging
DB_LOG_QUERIES=true
DB_SLOW_QUERY_THRESHOLD=100
```

### Программно

```php
// Изменить порог медленных запросов
QueryDebugger::setSlowQueryThreshold(200);

// Включить/выключить логирование
$db->enableQueryLog();
$db->disableQueryLog();

// Получить данные программно
$queries = QueryDebugger::getQueries();
$stats = QueryDebugger::getStats();
$slowQueries = QueryDebugger::getSlowQueries();
$duplicates = QueryDebugger::getDuplicates();
```

## ✨ Преимущества

1. **Полная прозрачность** - видны все SQL запросы
2. **Обнаружение проблем** - медленные запросы и N+1
3. **Caller tracking** - видно, откуда вызван запрос
4. **Детальная информация** - bindings, время, количество строк
5. **Подсветка синтаксиса** - легче читать SQL
6. **Статистика** - общее представление о нагрузке БД
7. **Автоматическая работа** - включается только в debug режиме

## 🚀 Использование

### Просто откройте приложение

1. Убедитесь, что `APP_DEBUG=true`
2. Откройте любую страницу
3. Внизу появится Debug Toolbar
4. Кликните на вкладку "Queries" (🗄️)
5. Анализируйте запросы!

### Пример оптимизации N+1

**До (плохо)**:
```php
$users = $this->db->table('users')->get();
foreach ($users as $user) {
    $posts = $this->db->table('posts')
        ->where('user_id', $user['id'])
        ->get();
}
// Debug Toolbar: 11 queries (10 duplicates)
```

**После (хорошо)**:
```php
$users = $this->db->select('
    SELECT u.*, COUNT(p.id) as posts_count
    FROM users u
    LEFT JOIN posts p ON u.id = p.user_id
    GROUP BY u.id
');
// Debug Toolbar: 1 query
```

## 🎯 Результат

- ✅ **Полная интеграция** DatabaseManager с Debug Toolbar
- ✅ **Автоматическое логирование** всех запросов
- ✅ **Красивое отображение** с подсветкой и статистикой
- ✅ **Инструменты анализа** (медленные, дубликаты)
- ✅ **Zero configuration** - работает из коробки
- ✅ **Production-safe** - отключается автоматически

## 📚 Документация

- `docs/DatabaseDebugToolbarIntegration.md` - полная документация
- `docs/DatabaseExamples.md` - примеры работы с БД
- `docs/QueryDebugger.md` - документация по QueryDebugger

---

**Теперь отладка SQL запросов стала простой и удобной! 🎉**

