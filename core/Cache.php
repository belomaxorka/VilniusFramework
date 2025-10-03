<?php declare(strict_types=1);

namespace Core;

use Core\Cache\CacheDriverInterface;
use Core\Cache\CacheManager;

/**
 * Cache Facade
 * 
 * Фасад для удобного доступа к системе кэширования
 * 
 * @method static mixed get(string $key, mixed $default = null)
 * @method static bool set(string $key, mixed $value, int|\DateInterval|null $ttl = null)
 * @method static bool delete(string $key)
 * @method static bool clear()
 * @method static iterable getMultiple(iterable $keys, mixed $default = null)
 * @method static bool setMultiple(iterable $values, int|\DateInterval|null $ttl = null)
 * @method static bool deleteMultiple(iterable $keys)
 * @method static bool has(string $key)
 * @method static int|false increment(string $key, int $value = 1)
 * @method static int|false decrement(string $key, int $value = 1)
 * @method static mixed pull(string $key, mixed $default = null)
 * @method static bool add(string $key, mixed $value, int|\DateInterval|null $ttl = null)
 * @method static bool forever(string $key, mixed $value)
 * @method static mixed remember(string $key, int|\DateInterval|null $ttl, \Closure $callback)
 * @method static mixed rememberForever(string $key, \Closure $callback)
 * @method static CacheDriverInterface driver(?string $name = null)
 */
class Cache
{
    protected static ?CacheManager $manager = null;

    /**
     * Получить экземпляр менеджера кэша
     */
    public static function getManager(): CacheManager
    {
        if (self::$manager === null) {
            $config = Config::get('cache');
            self::$manager = new CacheManager($config);
        }

        return self::$manager;
    }

    /**
     * Установить экземпляр менеджера кэша
     */
    public static function setManager(CacheManager $manager): void
    {
        self::$manager = $manager;
    }

    /**
     * Получить драйвер кэша
     */
    public static function driver(?string $name = null): CacheDriverInterface
    {
        return self::getManager()->driver($name);
    }

    /**
     * Получить значение из кэша
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::getManager()->get($key, $default);
    }

    /**
     * Сохранить значение в кэш
     */
    public static function set(string $key, mixed $value, int|\DateInterval|null $ttl = null): bool
    {
        return self::getManager()->set($key, $value, $ttl);
    }

    /**
     * Удалить значение из кэша
     */
    public static function delete(string $key): bool
    {
        return self::getManager()->delete($key);
    }

    /**
     * Очистить весь кэш
     */
    public static function clear(): bool
    {
        return self::getManager()->clear();
    }

    /**
     * Получить несколько значений
     */
    public static function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return self::getManager()->getMultiple($keys, $default);
    }

    /**
     * Сохранить несколько значений
     */
    public static function setMultiple(iterable $values, int|\DateInterval|null $ttl = null): bool
    {
        return self::getManager()->setMultiple($values, $ttl);
    }

    /**
     * Удалить несколько значений
     */
    public static function deleteMultiple(iterable $keys): bool
    {
        return self::getManager()->deleteMultiple($keys);
    }

    /**
     * Проверить существование ключа
     */
    public static function has(string $key): bool
    {
        return self::getManager()->has($key);
    }

    /**
     * Увеличить значение
     */
    public static function increment(string $key, int $value = 1): int|false
    {
        return self::getManager()->increment($key, $value);
    }

    /**
     * Уменьшить значение
     */
    public static function decrement(string $key, int $value = 1): int|false
    {
        return self::getManager()->decrement($key, $value);
    }

    /**
     * Получить значение и удалить
     */
    public static function pull(string $key, mixed $default = null): mixed
    {
        return self::getManager()->pull($key, $default);
    }

    /**
     * Сохранить значение, если ключ не существует
     */
    public static function add(string $key, mixed $value, int|\DateInterval|null $ttl = null): bool
    {
        return self::getManager()->add($key, $value, $ttl);
    }

    /**
     * Сохранить значение навсегда
     */
    public static function forever(string $key, mixed $value): bool
    {
        return self::getManager()->forever($key, $value);
    }

    /**
     * Запомнить значение, если его нет
     */
    public static function remember(string $key, int|\DateInterval|null $ttl, \Closure $callback): mixed
    {
        return self::getManager()->remember($key, $ttl, $callback);
    }

    /**
     * Запомнить значение навсегда, если его нет
     */
    public static function rememberForever(string $key, \Closure $callback): mixed
    {
        return self::getManager()->rememberForever($key, $callback);
    }

    /**
     * Очистить конкретный драйвер
     */
    public static function purge(?string $name = null): void
    {
        self::getManager()->purge($name);
    }

    /**
     * Добавить кастомный драйвер
     */
    public static function extend(string $name, string $driverClass): void
    {
        self::getManager()->extend($name, $driverClass);
    }
}

