<?php declare(strict_types=1);

namespace Core;

use Core\Facades\Facade;
use Core\Contracts\CacheInterface;

/**
 * Cache Facade
 * 
 * Статический фасад для CacheManager
 * Обеспечивает обратную совместимость со старым API
 * 
 * @method static mixed get(string $key, mixed $default = null)
 * @method static bool set(string $key, mixed $value, ?int $ttl = null)
 * @method static bool has(string $key)
 * @method static bool delete(string $key)
 * @method static bool clear()
 * @method static mixed remember(string $key, int $ttl, callable $callback)
 * @method static mixed rememberForever(string $key, callable $callback)
 * @method static mixed pull(string $key, mixed $default = null)
 * @method static bool add(string $key, mixed $value, ?int $ttl = null)
 * @method static bool forever(string $key, mixed $value)
 * @method static int|false increment(string $key, int $value = 1)
 * @method static int|false decrement(string $key, int $value = 1)
 * @method static bool deleteMultiple(array $keys)
 * @method static array getMultiple(array $keys, mixed $default = null)
 * @method static bool setMultiple(array $values, ?int $ttl = null)
 * @method static array getStats()
 * @method static \Core\Cache\CacheDriverInterface driver(?string $name = null)
 * @method static void purge(?string $name = null)
 * 
 * @see \Core\Cache\CacheManager
 */
class Cache extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CacheInterface::class;
    }
}
