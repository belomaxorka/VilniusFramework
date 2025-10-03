<?php declare(strict_types=1);

/**
 * Примеры использования системы кэширования Vilnius Framework
 */

require_once __DIR__ . '/../core/bootstrap.php';

use Core\Cache;
use Core\Database;

// ============================================================================
// Базовое использование
// ============================================================================

echo "=== Базовое использование ===\n\n";

// Сохранить значение на 1 час
Cache::set('user_name', 'John Doe', 3600);

// Получить значение
$name = Cache::get('user_name');
echo "User name: {$name}\n";

// Получить с default значением
$email = Cache::get('user_email', 'default@example.com');
echo "User email: {$email}\n\n";

// ============================================================================
// Remember - кэширование с callback
// ============================================================================

echo "=== Remember Pattern ===\n\n";

// Кэширование результата выполнения функции
$users = Cache::remember('all_users', 3600, function () {
    echo "Fetching users from database...\n";
    // В реальном приложении это был бы запрос к БД
    return ['John', 'Jane', 'Bob'];
});

print_r($users);

// При повторном вызове функция не выполнится, вернется кэшированное значение
$cachedUsers = Cache::remember('all_users', 3600, function () {
    echo "This won't be printed!\n";
    return [];
});

echo "Cached users: " . implode(', ', $cachedUsers) . "\n\n";

// ============================================================================
// Работа с несколькими значениями
// ============================================================================

echo "=== Множественные операции ===\n\n";

// Сохранить несколько значений
Cache::setMultiple([
    'product_1' => 'Laptop',
    'product_2' => 'Mouse',
    'product_3' => 'Keyboard',
], 3600);

// Получить несколько значений
$products = Cache::getMultiple(['product_1', 'product_2', 'product_3', 'product_4'], 'N/A');
print_r($products);

// Удалить несколько значений
Cache::deleteMultiple(['product_1', 'product_2']);
echo "Products 1 and 2 deleted\n\n";

// ============================================================================
// Инкремент и декремент
// ============================================================================

echo "=== Счетчики ===\n\n";

// Счетчик просмотров
Cache::set('page_views', 0);
Cache::increment('page_views');
Cache::increment('page_views', 5);
echo "Page views: " . Cache::get('page_views') . "\n";

// Инвентарь товара
Cache::set('product_stock', 100);
Cache::decrement('product_stock', 3);
echo "Product stock: " . Cache::get('product_stock') . "\n\n";

// ============================================================================
// Использование через хелперы
// ============================================================================

echo "=== Хелперы ===\n\n";

// cache() без аргументов возвращает менеджер
$manager = cache();
echo "Manager class: " . get_class($manager) . "\n";

// cache() с ключом получает значение
cache()->set('helper_test', 'Hello from helper!');
echo cache('helper_test') . "\n";

// cache_remember
$result = cache_remember('expensive_operation', 3600, function () {
    return 'Expensive result';
});
echo "Result: {$result}\n";

// cache_has
echo "Has key: " . (cache_has('helper_test') ? 'Yes' : 'No') . "\n";

// cache_forget
cache_forget('helper_test');
echo "Key deleted\n\n";

// ============================================================================
// Разные драйверы
// ============================================================================

echo "=== Разные драйверы ===\n\n";

// Array driver (in-memory)
$arrayCache = Cache::driver('array');
$arrayCache->set('temp_data', 'Only for this request');
echo "Array cache: " . $arrayCache->get('temp_data') . "\n";

// File driver
$fileCache = Cache::driver('file');
$fileCache->set('file_data', 'Stored in filesystem', 3600);
echo "File cache: " . $fileCache->get('file_data') . "\n\n";

// ============================================================================
// Практические примеры
// ============================================================================

echo "=== Практические примеры ===\n\n";

// 1. Кэширование конфигурации
function getCachedConfig($key)
{
    return Cache::remember("config:{$key}", 86400, function () use ($key) {
        // Загрузка из БД или файла
        return ['setting' => 'value'];
    });
}

$config = getCachedConfig('app');
print_r($config);

// 2. Rate Limiting (ограничение частоты запросов)
function checkRateLimit($userId, $maxAttempts = 100, $decaySeconds = 3600)
{
    $key = "rate_limit:{$userId}";
    $attempts = Cache::get($key, 0);
    
    if ($attempts >= $maxAttempts) {
        return false;
    }
    
    Cache::set($key, $attempts + 1, $decaySeconds);
    return true;
}

$canProceed = checkRateLimit('user_123');
echo "Can make request: " . ($canProceed ? 'Yes' : 'No') . "\n";

// 3. Lock механизм
function acquireLock($resource, $timeout = 10)
{
    $key = "lock:{$resource}";
    return Cache::add($key, true, $timeout);
}

function releaseLock($resource)
{
    Cache::delete("lock:{$resource}");
}

if (acquireLock('critical_section')) {
    echo "Lock acquired\n";
    // Выполнить критическую операцию
    releaseLock('critical_section');
    echo "Lock released\n";
} else {
    echo "Could not acquire lock\n";
}

// 4. Кэширование с тегами (через префиксы)
function cacheWithTag($tag, $key, $value, $ttl = 3600)
{
    Cache::set("{$tag}:{$key}", $value, $ttl);
}

function getCachedByTag($tag, $key, $default = null)
{
    return Cache::get("{$tag}:{$key}", $default);
}

function forgetTag($tag)
{
    // В реальном приложении нужно хранить список ключей для тега
    // Здесь упрощенный пример
    Cache::delete($tag);
}

cacheWithTag('users', 'list', ['John', 'Jane']);
$userList = getCachedByTag('users', 'list');
print_r($userList);

// ============================================================================
// Очистка кэша
// ============================================================================

echo "\n=== Очистка ===\n\n";

// Очистить весь кэш
// Cache::clear();
// echo "All cache cleared\n";

// Очистить конкретный драйвер
Cache::purge('array');
echo "Array cache cleared\n";

echo "\nПримеры завершены!\n";

