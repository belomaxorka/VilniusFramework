# Кэш - Быстрый старт

## Установка и настройка

### 1. Конфигурация

Откройте `config/cache.php` и настройте драйвер по умолчанию:

```php
return [
    'default' => 'file', // array, file, apcu, redis, memcached
];
```

### 2. Переменные окружения (опционально)

Добавьте в `.env`:

```env
CACHE_DRIVER=file

# Для Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_CACHE_DB=1

# Для Memcached
MEMCACHED_HOST=127.0.0.1
MEMCACHED_PORT=11211
```

## Базовое использование

### Сохранить и получить

```php
use Core\Cache;

// Сохранить на 1 час
Cache::set('key', 'value', 3600);

// Получить
$value = Cache::get('key');

// С default значением
$value = Cache::get('key', 'default');
```

### Проверить и удалить

```php
// Проверить существование
if (Cache::has('key')) {
    echo 'Exists!';
}

// Удалить
Cache::delete('key');

// Очистить весь кэш
Cache::clear();
```

## Remember Pattern

Самый удобный способ работы с кэшем:

```php
$users = Cache::remember('users', 3600, function () {
    return Database::table('users')->get();
});
```

Это автоматически:
1. Проверяет наличие ключа в кэше
2. Если есть - возвращает кэшированное значение
3. Если нет - выполняет callback и сохраняет результат

## Хелперы

```php
// Получить значение
$value = cache('key', 'default');

// Remember через хелпер
$users = cache_remember('users', 3600, fn() => Database::table('users')->get());

// Удалить
cache_forget('key');

// Очистить весь кэш
cache_flush();
```

## Счетчики

```php
// Увеличить
Cache::increment('views');
Cache::increment('counter', 5); // +5

// Уменьшить
Cache::decrement('stock');
Cache::decrement('inventory', 3); // -3

// Через хелперы
cache_increment('visits');
cache_decrement('items', 2);
```

## Практические примеры

### 1. Кэширование запросов к БД

```php
public function getPopularPosts()
{
    return Cache::remember('popular_posts', 3600, function () {
        return Database::table('posts')
            ->where('views', '>', 1000)
            ->orderBy('views', 'DESC')
            ->limit(10)
            ->get();
    });
}
```

### 2. Кэширование пользователя

```php
public function getUserById($id)
{
    return Cache::remember("user:{$id}", 3600, function () use ($id) {
        return Database::table('users')->find($id);
    });
}

public function updateUser($id, $data)
{
    Database::table('users')->where('id', $id)->update($data);
    
    // Инвалидация кэша
    Cache::forget("user:{$id}");
}
```

### 3. Счетчик просмотров

```php
public function trackPageView($pageId)
{
    $key = "page:{$pageId}:views";
    return Cache::increment($key);
}

public function getPageViews($pageId)
{
    return Cache::get("page:{$pageId}:views", 0);
}
```

### 4. Rate Limiting

```php
public function checkRateLimit($userId)
{
    $key = "rate_limit:{$userId}";
    $attempts = Cache::get($key, 0);
    
    if ($attempts >= 100) {
        throw new Exception('Too many requests');
    }
    
    Cache::set($key, $attempts + 1, 3600);
}
```

### 5. Временная блокировка

```php
public function processJob($jobId)
{
    $lockKey = "job_lock:{$jobId}";
    
    // Попытка получить блокировку на 30 секунд
    if (!Cache::add($lockKey, true, 30)) {
        throw new Exception('Job is already being processed');
    }
    
    try {
        // Выполнить работу
        $this->doWork($jobId);
    } finally {
        // Освободить блокировку
        Cache::delete($lockKey);
    }
}
```

## Выбор драйвера

### Array
- ✅ Быстрый
- ✅ Не требует установки
- ❌ Только для текущего запроса

**Когда использовать:** временные данные в рамках одного запроса

### File
- ✅ Не требует дополнительных сервисов
- ✅ Постоянное хранение
- ❌ Медленнее других

**Когда использовать:** небольшие проекты, разработка

### APCu
- ✅ Очень быстрый
- ✅ In-memory
- ❌ Требует расширение APCu
- ❌ Только на одном сервере

**Когда использовать:** один сервер, нужна скорость

### Redis
- ✅ Очень быстрый
- ✅ Распределенный
- ✅ Дополнительные возможности
- ❌ Требует Redis сервер

**Когда использовать:** продакшен, несколько серверов

### Memcached
- ✅ Быстрый
- ✅ Распределенный
- ✅ Большие объемы данных
- ❌ Требует Memcached сервер

**Когда использовать:** высоконагруженные системы

## Использование разных драйверов

```php
// Использовать конкретный драйвер
$redis = Cache::driver('redis');
$redis->set('key', 'value');

$file = Cache::driver('file');
$file->set('key', 'value');
```

## Множественные операции

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

## Внедрение через DI

```php
use Core\Cache\CacheManager;

class MyController extends Controller
{
    public function __construct(
        protected CacheManager $cache
    ) {}
    
    public function index()
    {
        $data = $this->cache->remember('data', 3600, function () {
            return $this->fetchData();
        });
        
        return $this->view('index', compact('data'));
    }
}
```

## Советы и лучшие практики

1. **Используйте префиксы для организации:**
   ```php
   Cache::set("user:{$id}:profile", $profile);
   Cache::set("post:{$id}:comments", $comments);
   ```

2. **Всегда инвалидируйте при изменении:**
   ```php
   public function updatePost($id, $data)
   {
       Database::table('posts')->update($id, $data);
       Cache::forget("post:{$id}");
   }
   ```

3. **Используйте remember вместо get/set:**
   ```php
   // ❌ Плохо
   $users = Cache::get('users');
   if (!$users) {
       $users = Database::table('users')->get();
       Cache::set('users', $users, 3600);
   }
   
   // ✅ Хорошо
   $users = Cache::remember('users', 3600, fn() => Database::table('users')->get());
   ```

4. **Устанавливайте разумные TTL:**
   - Статичные данные: 86400 (1 день) или больше
   - Часто меняющиеся: 300-3600 (5 минут - 1 час)
   - Очень динамичные: 60-300 (1-5 минут)

5. **Используйте правильный драйвер для окружения:**
   - Development: `file` или `array`
   - Production: `redis` или `memcached`

## Debug Toolbar

При `APP_DEBUG=true` вся статистика кэша доступна в Debug Toolbar:

```
🗃️ Cache
  Driver: file (file)
  Total: 15 | Hits: 10 | Misses: 3 | Writes: 2
  Hit Rate: 76.9%
```

Вы увидите каждую операцию с временем выполнения и значениями!

## Что дальше?

- [Полная документация по кэшу](Cache.md)
- [Debug Toolbar интеграция](CacheDebugToolbar.md)
- [Примеры использования](../examples/cache_examples.php)
- [Создание кастомных драйверов](Cache.md#добавление-кастомного-драйвера)

