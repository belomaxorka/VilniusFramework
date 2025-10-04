<?php declare(strict_types=1);

namespace Core;

use Core\Facades\Facade;
use Core\Contracts\ConfigInterface;
use ArrayAccess;
use Countable;

/**
 * Config Facade
 * 
 * Статический фасад для ConfigRepository
 * Обеспечивает обратную совместимость со старым API
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
 * @method static void prepend(string $key, mixed $value)
 * @method static mixed pull(string $key, mixed $default = null)
 * @method static void macro(string $key, callable $callback)
 * @method static void memoizedMacro(string $key, callable $callback)
 * @method static mixed resolve(string $key, mixed $default = null)
 * @method static bool isMacro(string $key)
 * @method static array resolveAll()
 * @method static void lock()
 * @method static void unlock()
 * @method static bool isLocked()
 * @method static bool cache(string $cachePath)
 * @method static bool loadCached(string $cachePath)
 * @method static bool isLoadedFromCache()
 * @method static bool isCached(string $cachePath)
 * @method static bool clearCache(string $cachePath)
 * @method static array|null getCacheInfo(string $cachePath)
 * @method static void setAllowedBasePaths(array $paths)
 * 
 * @see \Core\Services\ConfigRepository
 */
class Config extends Facade implements ArrayAccess, Countable
{
    protected static function getFacadeAccessor(): string
    {
        return ConfigInterface::class;
    }

    // ArrayAccess Implementation (делегируем к сервису)
    public function offsetExists(mixed $offset): bool
    {
        return static::has((string)$offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return static::get((string)$offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        static::set((string)$offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        static::forget((string)$offset);
    }

    // Countable Implementation
    public function count(): int
    {
        return count(static::all());
    }

    // Дополнительный метод для создания singleton instance (для ArrayAccess/Countable)
    public static function getInstance(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }
}
