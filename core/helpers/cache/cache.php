<?php declare(strict_types=1);

use Core\Cache;

if (!function_exists('cache')) {
    /**
     * Получить значение из кэша или менеджер кэша
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed|\Core\Cache\CacheManager
     */
    function cache(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return Cache::getManager();
        }

        return Cache::get($key, $default);
    }
}

if (!function_exists('cache_remember')) {
    /**
     * Получить значение из кэша или выполнить callback и сохранить результат
     *
     * @param string $key
     * @param int|\DateInterval|null $ttl
     * @param \Closure $callback
     * @return mixed
     */
    function cache_remember(string $key, int|\DateInterval|null $ttl, \Closure $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }
}

if (!function_exists('cache_forget')) {
    /**
     * Удалить значение из кэша
     *
     * @param string $key
     * @return bool
     */
    function cache_forget(string $key): bool
    {
        return Cache::delete($key);
    }
}

if (!function_exists('cache_flush')) {
    /**
     * Очистить весь кэш
     *
     * @return bool
     */
    function cache_flush(): bool
    {
        return Cache::clear();
    }
}

if (!function_exists('cache_has')) {
    /**
     * Проверить существование ключа в кэше
     *
     * @param string $key
     * @return bool
     */
    function cache_has(string $key): bool
    {
        return Cache::has($key);
    }
}

if (!function_exists('cache_pull')) {
    /**
     * Получить значение из кэша и удалить его
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function cache_pull(string $key, mixed $default = null): mixed
    {
        return Cache::pull($key, $default);
    }
}

if (!function_exists('cache_forever')) {
    /**
     * Сохранить значение в кэш навсегда
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    function cache_forever(string $key, mixed $value): bool
    {
        return Cache::forever($key, $value);
    }
}

if (!function_exists('cache_increment')) {
    /**
     * Увеличить значение в кэше
     *
     * @param string $key
     * @param int $value
     * @return int|false
     */
    function cache_increment(string $key, int $value = 1): int|false
    {
        return Cache::increment($key, $value);
    }
}

if (!function_exists('cache_decrement')) {
    /**
     * Уменьшить значение в кэше
     *
     * @param string $key
     * @param int $value
     * @return int|false
     */
    function cache_decrement(string $key, int $value = 1): int|false
    {
        return Cache::decrement($key, $value);
    }
}

