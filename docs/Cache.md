# Система кэширования

Vilnius Framework предоставляет мощную и гибкую систему кэширования с поддержкой различных драйверов.

## Поддерживаемые драйверы

- **Array** - хранение в памяти (только для текущего запроса)
- **File** - хранение в файловой системе
- **APCu** - хранение в APCu (in-memory cache)
- **Redis** - хранение в Redis
- **Memcached** - хранение в Memcached

## Конфигурация

Настройки кэша находятся в `config/cache.php`:

```php
return [
    'default' => 'file', // Драйвер по умолчанию
    
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => CACHE_DIR . '/data',
            'prefix' => 'vilnius_',
            'ttl' => 3600,
        ],
        
        'redis' => [
            'driver' => 'redis',
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => null,
            'database' => 1,
        ],
        
        // ... другие драйверы
    ],
];
```

## Использование через фасад

```php
use Core\Cache;

// Сохранить значение
Cache::set('key', 'value', 3600); // TTL в секундах

// Получить значение
$value = Cache::get('key');
$value = Cache::get('key', 'default'); // С default значением

// Проверить существование
if (Cache::has('key')) {
    // ...
}

// Удалить значение
Cache::delete('key');

// Очистить весь кэш
Cache::clear();
```

## Использование через хелперы

```php
// Получить значение
$value = cache('key');
$value = cache('key', 'default');

// Получить менеджер кэша
$manager = cache();

// Кэширование с callback
$users = cache_remember('users', 3600, function () {
    return Database::table('users')->get();
});

// Удалить
cache_forget('key');

// Очистить весь кэш
cache_flush();

// Проверить существование
if (cache_has('key')) {
    // ...
}
```

## Remember - кэширование с callback

Метод `remember` позволяет кэшировать результат выполнения функции:

```php
$users = Cache::remember('users', 3600, function () {
    return Database::table('users')->get();
});

// Кэширование навсегда
$settings = Cache::rememberForever('settings', function () {
    return Database::table('settings')->get();
});
```

## Работа с несколькими значениями

```php
// Получить несколько значений
$values = Cache::getMultiple(['key1', 'key2', 'key3']);

// Сохранить несколько значений
Cache::setMultiple([
    'key1' => 'value1',
    'key2' => 'value2',
], 3600);

// Удалить несколько значений
Cache::deleteMultiple(['key1', 'key2']);
```

## Инкремент и декремент

```php
// Увеличить значение
Cache::increment('counter'); // +1
Cache::increment('counter', 5); // +5

// Уменьшить значение
Cache::decrement('counter'); // -1
Cache::decrement('counter', 3); // -3

// Через хелперы
cache_increment('views');
cache_decrement('stock', 2);
```

## Дополнительные методы

```php
// Получить и удалить
$value = Cache::pull('key');

// Добавить только если не существует
if (Cache::add('key', 'value', 3600)) {
    echo 'Значение добавлено';
}

// Сохранить навсегда
Cache::forever('key', 'value');
```

## Использование разных драйверов

```php
// Получить конкретный драйвер
$redis = Cache::driver('redis');
$redis->set('key', 'value');

// Использовать file cache
$file = Cache::driver('file');
$file->set('key', 'value', 3600);

// Использовать array cache
$array = Cache::driver('array');
$array->set('key', 'value');
```

## Работа с TTL

```php
// TTL в секундах
Cache::set('key', 'value', 3600); // 1 час

// TTL через DateInterval
Cache::set('key', 'value', new DateInterval('P1D')); // 1 день

// Без ограничения времени
Cache::set('key', 'value', null);
Cache::forever('key', 'value');
```

## Примеры использования

### Кэширование результатов запросов

```php
public function getUsers()
{
    return Cache::remember('all_users', 3600, function () {
        return Database::table('users')->get();
    });
}
```

### Кэширование представлений

```php
public function renderWidget()
{
    return Cache::remember('widget_html', 1800, function () {
        return view('widgets.popular')->render();
    });
}
```

### Счетчик просмотров

```php
public function incrementViews($postId)
{
    $key = "post:{$postId}:views";
    return Cache::increment($key);
}
```

### Ограничение частоты запросов (Rate Limiting)

```php
public function checkRateLimit($userId)
{
    $key = "rate_limit:{$userId}";
    $attempts = Cache::get($key, 0);
    
    if ($attempts >= 100) {
        return false; // Превышен лимит
    }
    
    Cache::set($key, $attempts + 1, 3600);
    return true;
}
```

### Блокировка (Lock)

```php
public function acquireLock($resource, $timeout = 10)
{
    $key = "lock:{$resource}";
    return Cache::add($key, true, $timeout);
}

public function releaseLock($resource)
{
    $key = "lock:{$resource}";
    Cache::delete($key);
}
```

## Очистка кэша

```php
// Очистить весь кэш
Cache::clear();
Cache::purge();

// Очистить конкретный драйвер
Cache::purge('redis');

// Через хелпер
cache_flush();
```

## Специфичные методы драйверов

### File Driver

```php
$file = Cache::driver('file');

// Запустить сборку мусора (удаление просроченных файлов)
$deleted = $file->gc();
```

### Redis Driver

```php
$redis = Cache::driver('redis');

// Получить TTL ключа
$ttl = $redis->ttl('key');

// Получить информацию о Redis
$info = $redis->info();

// Получить экземпляр Redis для прямой работы
$redisInstance = $redis->getRedis();
```

### APCu Driver

```php
$apcu = Cache::driver('apcu');

// Получить информацию о кэше
$info = $apcu->info();

// Получить информацию о памяти
$memory = $apcu->smaInfo();
```

### Memcached Driver

```php
$memcached = Cache::driver('memcached');

// Получить статистику серверов
$stats = $memcached->getStats();

// Получить версию
$version = $memcached->getVersion();
```

## Добавление кастомного драйвера

```php
use Core\Cache;
use Core\Cache\CacheDriverInterface;
use Core\Cache\AbstractCacheDriver;

class MyCustomDriver extends AbstractCacheDriver
{
    public function get(string $key, mixed $default = null): mixed
    {
        // Ваша реализация
    }
    
    public function set(string $key, mixed $value, int|\DateInterval|null $ttl = null): bool
    {
        // Ваша реализация
    }
    
    // ... остальные методы
}

// Регистрация драйвера
Cache::extend('mycustom', MyCustomDriver::class);
```

## Внедрение через DI

```php
use Core\Cache\CacheManager;

class UserController extends Controller
{
    public function __construct(
        protected CacheManager $cache
    ) {}
    
    public function index()
    {
        $users = $this->cache->remember('users', 3600, function () {
            return Database::table('users')->get();
        });
        
        return $this->view('users.index', compact('users'));
    }
}
```

## Тестирование

При тестировании рекомендуется использовать Array драйвер:

```php
// В тестах
beforeEach(function () {
    Cache::setManager(new CacheManager([
        'default' => 'array',
        'stores' => [
            'array' => [
                'driver' => 'array',
                'prefix' => 'test_',
            ],
        ],
    ]));
});

afterEach(function () {
    Cache::clear();
});
```

## Лучшие практики

1. **Используйте префиксы** для организации ключей:
   ```php
   Cache::set("user:{$userId}:profile", $profile);
   Cache::set("post:{$postId}:comments", $comments);
   ```

2. **Устанавливайте разумные TTL** - не храните данные дольше, чем нужно

3. **Используйте remember** вместо get/set паттерна:
   ```php
   // Плохо
   $users = Cache::get('users');
   if (!$users) {
       $users = Database::table('users')->get();
       Cache::set('users', $users, 3600);
   }
   
   // Хорошо
   $users = Cache::remember('users', 3600, fn() => Database::table('users')->get());
   ```

4. **Инвалидируйте кэш** при изменении данных:
   ```php
   public function updateUser($id, $data)
   {
       Database::table('users')->where('id', $id)->update($data);
       Cache::forget("user:{$id}");
       Cache::forget('all_users');
   }
   ```

5. **Используйте правильный драйвер** для ваших нужд:
   - **Array** - для данных в рамках одного запроса
   - **File** - для небольших проектов без дополнительных зависимостей
   - **APCu** - для быстрого in-memory кэша на одном сервере
   - **Redis** - для распределенных систем и продакшена
   - **Memcached** - для распределенного кэширования больших объемов данных

## Переменные окружения

Настройте кэш через `.env`:

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

