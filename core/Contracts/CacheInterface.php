<?php declare(strict_types=1);

namespace Core\Contracts;

/**
 * Cache Manager Interface
 * 
 * Определяет контракт для работы с кешированием
 */
interface CacheInterface
{
    /**
     * Получить значение из кеша
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Сохранить значение в кеш
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Проверить существование ключа в кеше
     */
    public function has(string $key): bool;

    /**
     * Удалить значение из кеша
     */
    public function delete(string $key): bool;

    /**
     * Очистить весь кеш
     */
    public function clear(): bool;

    /**
     * Получить или сохранить значение
     * 
     * Если ключ существует, возвращает его.
     * Если нет - вызывает callback, сохраняет результат и возвращает его.
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;

    /**
     * Получить или сохранить значение навсегда
     */
    public function rememberForever(string $key, callable $callback): mixed;

    /**
     * Получить значение и удалить его
     */
    public function pull(string $key, mixed $default = null): mixed;

    /**
     * Добавить значение только если ключа нет
     */
    public function add(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Сохранить значение навсегда
     */
    public function forever(string $key, mixed $value): bool;

    /**
     * Увеличить числовое значение
     */
    public function increment(string $key, int $value = 1): int|false;

    /**
     * Уменьшить числовое значение
     */
    public function decrement(string $key, int $value = 1): int|false;

    /**
     * Удалить несколько ключей
     */
    public function deleteMultiple(array $keys): bool;

    /**
     * Получить несколько значений
     */
    public function getMultiple(array $keys, mixed $default = null): array;

    /**
     * Сохранить несколько значений
     */
    public function setMultiple(array $values, ?int $ttl = null): bool;

    /**
     * Получить информацию о кеше
     */
    public function getStats(): array;
}

