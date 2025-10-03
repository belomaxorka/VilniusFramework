<?php declare(strict_types=1);

namespace Core\Cache;

interface CacheDriverInterface
{
    /**
     * Получить значение из кэша
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Сохранить значение в кэш
     *
     * @param string $key
     * @param mixed $value
     * @param int|\DateInterval|null $ttl Время жизни в секундах (null = без ограничения)
     * @return bool
     */
    public function set(string $key, mixed $value, int|\DateInterval|null $ttl = null): bool;

    /**
     * Удалить значение из кэша
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * Очистить весь кэш
     *
     * @return bool
     */
    public function clear(): bool;

    /**
     * Получить несколько значений
     *
     * @param iterable $keys
     * @param mixed $default
     * @return iterable
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable;

    /**
     * Сохранить несколько значений
     *
     * @param iterable $values
     * @param int|\DateInterval|null $ttl
     * @return bool
     */
    public function setMultiple(iterable $values, int|\DateInterval|null $ttl = null): bool;

    /**
     * Удалить несколько значений
     *
     * @param iterable $keys
     * @return bool
     */
    public function deleteMultiple(iterable $keys): bool;

    /**
     * Проверить существование ключа
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Увеличить значение
     *
     * @param string $key
     * @param int $value
     * @return int|false
     */
    public function increment(string $key, int $value = 1): int|false;

    /**
     * Уменьшить значение
     *
     * @param string $key
     * @param int $value
     * @return int|false
     */
    public function decrement(string $key, int $value = 1): int|false;

    /**
     * Получить значение и удалить
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function pull(string $key, mixed $default = null): mixed;

    /**
     * Сохранить значение, если ключ не существует
     *
     * @param string $key
     * @param mixed $value
     * @param int|\DateInterval|null $ttl
     * @return bool
     */
    public function add(string $key, mixed $value, int|\DateInterval|null $ttl = null): bool;

    /**
     * Сохранить значение навсегда
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function forever(string $key, mixed $value): bool;

    /**
     * Запомнить значение, если его нет
     *
     * @param string $key
     * @param int|\DateInterval|null $ttl
     * @param \Closure $callback
     * @return mixed
     */
    public function remember(string $key, int|\DateInterval|null $ttl, \Closure $callback): mixed;

    /**
     * Запомнить значение навсегда, если его нет
     *
     * @param string $key
     * @param \Closure $callback
     * @return mixed
     */
    public function rememberForever(string $key, \Closure $callback): mixed;
}

