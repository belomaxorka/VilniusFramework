<?php declare(strict_types=1);

namespace Core;

use Core\Facades\Facade;
use Core\Contracts\ConfigInterface;

/**
 * Config Facade
 * 
 * Статический фасад для ConfigRepository
 * Все методы делегируются к ConfigInterface через DI контейнер
 * 
 * @method static void load(string $path, ?string $environment = null, bool $recursive = false)
 * @method static void loadFile(string $filePath)
 * @method static mixed get(string $key, mixed $default = null)
 * @method static void set(string $key, mixed $value)
 * @method static bool has(string $key)
 * @method static void forget(string $key)
 * @method static array all()
 * @method static void clear()
 * @method static mixed getRequired(string $key)
 * @method static array getMany(array $keys, mixed $default = null)
 * @method static void push(string $key, mixed $value)
 * @method static mixed resolve(string $key, mixed $default = null)
 * @method static void lock()
 * @method static void unlock()
 * @method static bool isLocked()
 * @method static bool cache(string $cachePath)
 * @method static bool loadCached(string $cachePath)
 * @method static bool isLoadedFromCache()
 * @method static bool isCached(string $cachePath)
 * @method static bool clearCache(string $cachePath)
 * @method static array|null getCacheInfo(string $cachePath)
 * 
 * @see \Core\Services\ConfigRepository
 */
class Config extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ConfigInterface::class;
    }
}
