<?php declare(strict_types=1);

namespace Core\Cache\Drivers;

use Core\Cache\AbstractCacheDriver;
use DateInterval;

/**
 * In-memory cache driver (для использования в рамках одного запроса)
 */
class ArrayDriver extends AbstractCacheDriver
{
    protected array $storage = [];
    protected array $expiration = [];

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $startTime = microtime(true);
        $originalKey = $key;
        $key = $this->getKey($key);

        if (!isset($this->storage[$key])) {
            $this->logGet($originalKey, false, null, $startTime);
            return $default;
        }

        // Проверяем срок годности
        if (isset($this->expiration[$key]) && $this->expiration[$key] < time()) {
            $this->delete($originalKey);
            $this->logGet($originalKey, false, null, $startTime);
            return $default;
        }

        $value = $this->storage[$key];
        $this->logGet($originalKey, true, $value, $startTime);
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        $startTime = microtime(true);
        $originalKey = $key;
        $key = $this->getKey($key);
        $this->storage[$key] = $value;

        $ttl = $this->normalizeTtl($ttl);
        if ($ttl !== null) {
            $this->expiration[$key] = time() + $ttl;
        } else {
            unset($this->expiration[$key]);
        }

        $this->logSet($originalKey, $value, $startTime);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $startTime = microtime(true);
        $originalKey = $key;
        $key = $this->getKey($key);
        unset($this->storage[$key], $this->expiration[$key]);
        $this->logDelete($originalKey, $startTime);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $this->storage = [];
        $this->expiration = [];
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $key, int $value = 1): int|false
    {
        $current = $this->get($key, 0);

        if (!is_numeric($current)) {
            return false;
        }

        $new = (int)$current + $value;
        $this->set($key, $new);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(string $key, int $value = 1): int|false
    {
        return $this->increment($key, -$value);
    }

    /**
     * Получить все данные (для отладки)
     */
    public function all(): array
    {
        return $this->storage;
    }
}

