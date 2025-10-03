# Cache - Шпаргалка

## Быстрые команды

```php
use Core\Cache;

// Сохранить
Cache::set('key', 'value', 3600);

// Получить
$value = Cache::get('key');
$value = Cache::get('key', 'default');

// Проверить
Cache::has('key');

// Удалить
Cache::delete('key');

// Очистить всё
Cache::clear();
```

## Хелперы

```php
cache('key')                              // Получить
cache('key', 'default')                   // Получить с default
cache_remember('key', 3600, $callback)    // Remember
cache_forget('key')                       // Удалить
cache_flush()                             // Очистить всё
cache_has('key')                          // Проверить
cache_pull('key')                         // Получить и удалить
cache_forever('key', 'value')             // Сохранить навсегда
cache_increment('counter')                // +1
cache_decrement('counter')                // -1
```

## Remember Pattern

```php
// Базовый
$users = Cache::remember('users', 3600, function () {
    return Database::table('users')->get();
});

// Навсегда
$settings = Cache::rememberForever('settings', function () {
    return Database::table('settings')->get();
});
```

## Счетчики

```php
Cache::increment('views');               // +1
Cache::increment('counter', 5);          // +5
Cache::decrement('stock');               // -1
Cache::decrement('inventory', 3);        // -3
```

## Множественные операции

```php
// Получить много
$values = Cache::getMultiple(['key1', 'key2', 'key3']);

// Сохранить много
Cache::setMultiple([
    'key1' => 'value1',
    'key2' => 'value2',
], 3600);

// Удалить много
Cache::deleteMultiple(['key1', 'key2']);
```

## Дополнительные методы

```php
// Получить и удалить
$value = Cache::pull('key');

// Добавить только если не существует
Cache::add('key', 'value', 3600);

// Сохранить навсегда
Cache::forever('key', 'value');
```

## Драйверы

```php
// Использовать конкретный драйвер
$redis = Cache::driver('redis');
$file = Cache::driver('file');
$apcu = Cache::driver('apcu');

// Очистить конкретный драйвер
Cache::purge('redis');
```

## TTL форматы

```php
Cache::set('key', 'value', 3600);              // Секунды
Cache::set('key', 'value', new DateInterval('P1D'));  // DateInterval
Cache::set('key', 'value', null);              // Навсегда
```

## Паттерны

### Кэширование запроса к БД
```php
$users = Cache::remember('users', 3600, fn() => 
    Database::table('users')->get()
);
```

### Инвалидация при обновлении
```php
public function updateUser($id, $data) {
    Database::table('users')->update($id, $data);
    Cache::forget("user:{$id}");
}
```

### Rate Limiting
```php
$key = "rate:{$userId}";
if (Cache::increment($key) > 100) {
    throw new Exception('Too many requests');
}
Cache::set($key, Cache::get($key), 3600);
```

### Блокировка (Lock)
```php
if (Cache::add("lock:{$resource}", true, 10)) {
    // Критическая секция
    Cache::delete("lock:{$resource}");
}
```

### Счетчик просмотров
```php
Cache::increment("views:{$postId}");
$views = Cache::get("views:{$postId}", 0);
```

## Конфигурация

### .env
```env
CACHE_DRIVER=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_CACHE_DB=1

# Memcached
MEMCACHED_HOST=127.0.0.1
MEMCACHED_PORT=11211
```

### config/cache.php
```php
return [
    'default' => 'file',
    'stores' => [
        'file' => [...],
        'redis' => [...],
        'memcached' => [...],
    ],
];
```

## DI в контроллерах

```php
use Core\Cache\CacheManager;

class MyController extends Controller
{
    public function __construct(
        protected CacheManager $cache
    ) {}
    
    public function index()
    {
        $data = $this->cache->get('data');
        return $this->view('index', compact('data'));
    }
}
```

## Именование ключей

```php
// ✅ Хорошо - структурированные ключи
Cache::set("user:{$id}:profile", $profile);
Cache::set("post:{$id}:comments", $comments);
Cache::set("category:{$slug}:products", $products);

// ❌ Плохо - неструктурированные
Cache::set("userprofile{$id}", $profile);
Cache::set("comments", $comments);
```

## Выбор драйвера

| Драйвер | Скорость | Персистентность | Распределенность | Установка |
|---------|----------|-----------------|------------------|-----------|
| Array | ⚡⚡⚡ | ❌ | ❌ | ✅ |
| File | ⚡ | ✅ | ❌ | ✅ |
| APCu | ⚡⚡⚡ | ✅ | ❌ | ext-apcu |
| Redis | ⚡⚡ | ✅ | ✅ | Redis сервер |
| Memcached | ⚡⚡ | ✅ | ✅ | Memcached сервер |

## Рекомендации TTL

```php
// Статичные данные
Cache::set('config', $config, 86400);        // 1 день

// Редко меняющиеся
Cache::set('categories', $categories, 3600); // 1 час

// Часто обновляемые
Cache::set('online_users', $users, 300);     // 5 минут

// Очень динамичные
Cache::set('rate_limit', $count, 60);        // 1 минута
```

## Отладка

```php
// Проверить наличие
if (Cache::has('key')) {
    echo "Key exists";
}

// Получить с логированием
$value = Cache::get('key');
if ($value === null) {
    logger()->warning("Cache miss for key: key");
}

// Очистить всё при тестировании
Cache::clear();
```

## Частые ошибки

```php
// ❌ Плохо - забыли про инвалидацию
Cache::set('users', $users);
Database::table('users')->insert($newUser);

// ✅ Хорошо
Cache::set('users', $users);
Database::table('users')->insert($newUser);
Cache::forget('users');

// ❌ Плохо - кэшируем слишком долго
Cache::set('current_time', time(), 86400);

// ✅ Хорошо
Cache::set('current_time', time(), 60);

// ❌ Плохо - не используем remember
$users = Cache::get('users');
if (!$users) {
    $users = Database::table('users')->get();
    Cache::set('users', $users);
}

// ✅ Хорошо
$users = Cache::remember('users', 3600, fn() =>
    Database::table('users')->get()
);
```

