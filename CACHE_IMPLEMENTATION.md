# Реализация системы кэширования

## Обзор

Добавлена полноценная система кэширования с поддержкой множества драйверов и удобным API.

## Что реализовано

### 1. Архитектура

- **CacheDriverInterface** - интерфейс для всех драйверов кэша
- **AbstractCacheDriver** - базовый класс с общей логикой
- **CacheManager** - менеджер для управления драйверами
- **Cache Facade** - фасад для удобного использования

### 2. Драйверы

#### ArrayDriver
- Хранение в памяти (только для текущего запроса)
- Быстрый, не требует зависимостей
- Идеален для временных данных

#### FileDriver
- Хранение в файловой системе
- Атомарная запись, структурированное хранение
- Метод сборки мусора (gc)
- Не требует дополнительных сервисов

#### ApcuDriver
- Хранение в APCu (in-memory)
- Очень быстрый
- Поддержка массовых операций
- Методы info() и smaInfo() для мониторинга

#### RedisDriver
- Хранение в Redis
- Поддержка всех операций Redis
- Автоматическая сериализация
- Методы для работы с TTL и прямого доступа к Redis

#### MemcachedDriver
- Хранение в Memcached
- Поддержка кластеров серверов
- Конфигурируемые опции
- Статистика и мониторинг

### 3. API

Все драйверы поддерживают единый API:

```php
// Базовые операции
get(string $key, mixed $default = null): mixed
set(string $key, mixed $value, int|\DateInterval|null $ttl = null): bool
delete(string $key): bool
has(string $key): bool
clear(): bool

// Массовые операции
getMultiple(iterable $keys, mixed $default = null): iterable
setMultiple(iterable $values, int|\DateInterval|null $ttl = null): bool
deleteMultiple(iterable $keys): bool

// Счетчики
increment(string $key, int $value = 1): int|false
decrement(string $key, int $value = 1): int|false

// Дополнительные методы
pull(string $key, mixed $default = null): mixed
add(string $key, mixed $value, int|\DateInterval|null $ttl = null): bool
forever(string $key, mixed $value): bool
remember(string $key, int|\DateInterval|null $ttl, \Closure $callback): mixed
rememberForever(string $key, \Closure $callback): mixed
```

### 4. Фасад и хелперы

**Фасад Cache:**
```php
use Core\Cache;

Cache::set('key', 'value', 3600);
$value = Cache::get('key');
Cache::remember('users', 3600, fn() => Database::table('users')->get());
```

**Хелперы:**
```php
cache('key', 'default')
cache_remember('key', 3600, $callback)
cache_forget('key')
cache_flush()
cache_has('key')
cache_pull('key')
cache_forever('key', 'value')
cache_increment('counter')
cache_decrement('stock')
```

### 5. Конфигурация

Файл `config/cache.php` с настройками для всех драйверов:

```php
return [
    'default' => 'file',
    
    'stores' => [
        'array' => [...],
        'file' => [...],
        'apcu' => [...],
        'redis' => [...],
        'memcached' => [...],
    ],
];
```

### 6. Интеграция

- Регистрация в контейнере через `config/services.php`
- Загрузка хелперов через `core/bootstrap.php`
- Поддержка DI в контроллерах

### 7. Тестирование

Создано 4 файла тестов:
- `ArrayDriverTest.php` - тесты драйвера Array
- `FileDriverTest.php` - тесты драйвера File
- `CacheManagerTest.php` - тесты менеджера
- `CacheFacadeTest.php` - тесты фасада
- `CacheHelpersTest.php` - тесты хелперов

### 8. Документация

- `docs/Cache.md` - полная документация
- `docs/CacheQuickStart.md` - быстрый старт
- `examples/cache_examples.php` - примеры использования
- Обновлен `README.md` с информацией о кэше

## Структура файлов

```
core/
├── Cache.php                           # Фасад
├── Cache/
│   ├── CacheDriverInterface.php        # Интерфейс драйвера
│   ├── AbstractCacheDriver.php         # Базовый класс
│   ├── CacheManager.php                # Менеджер
│   ├── Exceptions/
│   │   ├── CacheException.php
│   │   └── InvalidArgumentException.php
│   └── Drivers/
│       ├── ArrayDriver.php
│       ├── FileDriver.php
│       ├── ApcuDriver.php
│       ├── RedisDriver.php
│       └── MemcachedDriver.php
└── helpers/
    └── cache/
        └── cache.php                   # Хелперы

config/
└── cache.php                           # Конфигурация

docs/
├── Cache.md                            # Документация
└── CacheQuickStart.md                  # Быстрый старт

examples/
└── cache_examples.php                  # Примеры

tests/Unit/Core/Cache/
├── ArrayDriverTest.php
├── FileDriverTest.php
├── CacheManagerTest.php
├── CacheFacadeTest.php
└── CacheHelpersTest.php
```

## Особенности реализации

### 1. Поддержка префиксов
Каждый драйвер поддерживает префиксы ключей для избежания коллизий.

### 2. Нормализация TTL
Автоматическая конвертация `DateInterval` в секунды.

### 3. Сериализация
Все драйверы корректно работают с объектами и массивами.

### 4. Атомарные операции
FileDriver использует временные файлы для атомарной записи.

### 5. Переподключение
RedisDriver автоматически обрабатывает потерю соединения.

### 6. Расширяемость
Легко добавить кастомные драйверы через `Cache::extend()`.

### 7. Множественные операции
Оптимизированные методы для работы с несколькими ключами.

## Примеры использования

### Кэширование запросов
```php
$users = Cache::remember('users', 3600, fn() => 
    Database::table('users')->get()
);
```

### Rate Limiting
```php
if (Cache::increment("rate:{$userId}") > 100) {
    throw new Exception('Too many requests');
}
```

### Блокировки
```php
if (Cache::add("lock:{$resource}", true, 10)) {
    // Выполнить работу
    Cache::delete("lock:{$resource}");
}
```

### Счетчики
```php
Cache::increment("page:{$id}:views");
Cache::decrement("product:{$id}:stock", $quantity);
```

## Производительность

- **Array**: Самый быстрый, но только для текущего запроса
- **APCu**: ~0.1ms на операцию
- **Redis**: ~1-2ms на операцию (по сети)
- **Memcached**: ~1-2ms на операцию (по сети)
- **File**: ~5-10ms на операцию (зависит от I/O)

## Совместимость

- PHP 8.1+
- Опциональные расширения:
  - ext-apcu (для ApcuDriver)
  - ext-redis (для RedisDriver)
  - ext-memcached (для MemcachedDriver)

## Что дальше

Возможные улучшения:
1. Поддержка тегов для группового удаления
2. Cache warming
3. Статистика использования
4. Автоматическая инвалидация
5. Драйвер для DynamoDB
6. Cache::many() для массового remember
7. События (cache.hit, cache.miss, cache.write)

## Автор

Система кэширования разработана для Vilnius Framework.

