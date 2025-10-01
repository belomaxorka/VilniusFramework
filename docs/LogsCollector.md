# Logs Collector - Коллектор логов приложения

## Обзор

**LogsCollector** отображает все логи, созданные во время текущего запроса через систему `Logger`.

## Возможности

### 📊 Что показывает:
- Все логи текущего запроса (debug, info, warning, error, critical)
- Статистика по уровням
- Временные метки
- Контекст каждого лога
- Цветовая кодировка по уровням

### 🎨 Визуализация:
- **Таблица логов** с фильтрацией
- **Статистика** по уровням
- **Контекст** в expandable блоках
- **Badge** с количеством errors/critical

## Использование

### Базовое логирование

```php
use Core\Logger;

// Debug информация
Logger::debug('User action', ['user_id' => 123, 'action' => 'login']);

// Информационные сообщения
Logger::info('Payment processed', ['amount' => 100, 'currency' => 'USD']);

// Предупреждения
Logger::warning('Slow query detected', ['query' => 'SELECT...', 'time' => 2.5]);

// Ошибки
Logger::error('Database connection failed', ['host' => 'localhost']);

// Критические ошибки
Logger::critical('System failure', ['error' => 'Out of memory']);
```

### С контекстом

```php
Logger::info('User {username} logged in from {ip}', [
    'username' => 'john_doe',
    'ip' => '192.168.1.1',
    'timestamp' => time()
]);
// Результат: "User john_doe logged in from 192.168.1.1"
```

## Визуальное отображение

### Статистика
```
┌─────────┬─────────┬──────────┬────────┬──────────┐
│ DEBUG   │ INFO    │ WARNING  │ ERROR  │ CRITICAL │
│   12    │   45    │    3     │   2    │    0     │
└─────────┴─────────┴──────────┴────────┴──────────┘
```

### Таблица логов
```
┌──────────┬──────────────────────┬────────────────────────────┬──────────┐
│ Level    │ Time                 │ Message                    │ Context  │
├──────────┼──────────────────────┼────────────────────────────┼──────────┤
│ ERROR    │ 2025-10-01 10:30:45  │ Database connection failed │ [View]   │
│ WARNING  │ 2025-10-01 10:30:46  │ Slow query detected        │ [View]   │
│ INFO     │ 2025-10-01 10:30:47  │ User logged in             │ [View]   │
└──────────┴──────────────────────┴────────────────────────────┴──────────┘
```

## Цветовая кодировка

- 🔵 **DEBUG** - серо-синий (#78909c)
- 🔷 **INFO** - синий (#42a5f5)
- 🟠 **WARNING** - оранжевый (#ffa726)
- 🔴 **ERROR** - красный (#ef5350)
- 🔴 **CRITICAL** - темно-красный (#c62828)

## Header Stats

В заголовке toolbar отображается:
- При наличии critical/errors: `📝 2 errors` (красный)
- При наличии warnings: `📝 3 warnings` (оранжевый)
- Иначе: `📝 15 logs` (зеленый)

## Badge

На вкладке отображается:
- Количество critical + errors (если есть)
- Общее количество логов (если нет ошибок)

## API

### Logger::getLogs()
Получает все логи текущего запроса:
```php
$logs = Logger::getLogs();
// [
//     ['level' => 'info', 'message' => '...', 'context' => [...], 'time' => ...],
//     ...
// ]
```

### Logger::getStats()
Получает статистику:
```php
$stats = Logger::getStats();
// [
//     'total' => 15,
//     'by_level' => [
//         'debug' => 5,
//         'info' => 8,
//         'warning' => 2,
//         ...
//     ]
// ]
```

### Logger::clearLogs()
Очищает логи (для тестирования):
```php
Logger::clearLogs();
```

## Примеры использования

### Логирование запросов к API
```php
Logger::debug('API Request', [
    'method' => 'POST',
    'endpoint' => '/api/users',
    'data' => $requestData
]);
```

### Логирование ошибок с stack trace
```php
try {
    // код
} catch (\Exception $e) {
    Logger::error('Exception caught', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
```

### Логирование производительности
```php
$start = microtime(true);
// ... операция ...
$time = (microtime(true) - $start) * 1000;

if ($time > 100) {
    Logger::warning('Slow operation detected', [
        'operation' => 'image_processing',
        'time_ms' => $time
    ]);
}
```

## Интеграция

LogsCollector автоматически регистрируется в Debug Toolbar и не требует дополнительной настройки.

Приоритет: **65** (между Timers и Memory)

## Производительность

- ✅ Минимальное влияние на производительность
- ✅ Логи хранятся только в памяти текущего запроса
- ✅ Автоматическая очистка после запроса
- ✅ Отключается в production (если Environment не debug)

## Советы

1. **Используйте правильные уровни:**
   - `debug` - детальная отладочная информация
   - `info` - информационные сообщения
   - `warning` - предупреждения, не критичные проблемы
   - `error` - ошибки, требующие внимания
   - `critical` - критические ошибки, система не работает

2. **Добавляйте контекст:**
   - Всегда передавайте полезную информацию в context
   - Используйте плейсхолдеры в сообщениях

3. **Не логируйте чувствительные данные:**
   - Пароли, токены, ключи API
   - Персональные данные пользователей

## Что дальше?

Следующие возможности:
- Фильтрация по уровням прямо в toolbar
- Экспорт логов в файл
- Поиск по логам
- Группировка похожих логов


