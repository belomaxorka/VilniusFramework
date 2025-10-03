<?php declare(strict_types=1);

namespace Core\Cache\Drivers;

use Core\Cache\AbstractCacheDriver;
use Core\Cache\Exceptions\CacheException;
use DateInterval;

/**
 * APCu cache driver
 */
class ApcuDriver extends AbstractCacheDriver
{
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        if (!extension_loaded('apcu')) {
            throw new CacheException('APCu extension is not loaded');
        }

        if (!ini_get('apc.enabled')) {
            throw new CacheException('APCu is not enabled');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $startTime = microtime(true);
        $originalKey = $key;
        $key = $this->getKey($key);
        $value = apcu_fetch($key, $success);

        if (!$success) {
            $this->logGet($originalKey, false, null, $startTime);
            return $default;
        }

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
        $ttl = $this->normalizeTtl($ttl) ?? 0;

        $result = apcu_store($key, $value, $ttl);
        $this->logSet($originalKey, $value, $startTime);
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $startTime = microtime(true);
        $originalKey = $key;
        $key = $this->getKey($key);
        $result = apcu_delete($key);
        $this->logDelete($originalKey, $startTime);
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return apcu_clear_cache();
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $key = $this->getKey($key);
        return apcu_exists($key);
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $key, int $value = 1): int|false
    {
        $key = $this->getKey($key);
        
        $result = apcu_inc($key, $value, $success);
        
        if (!$success) {
            // Если ключ не существует, создаем его
            apcu_store($key, $value, 0);
            return $value;
        }
        
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(string $key, int $value = 1): int|false
    {
        $key = $this->getKey($key);
        
        $result = apcu_dec($key, $value, $success);
        
        if (!$success) {
            // Если ключ не существует, создаем его
            apcu_store($key, -$value, 0);
            return -$value;
        }
        
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $prefixedKeys = [];
        foreach ($keys as $key) {
            $prefixedKeys[] = $this->getKey($key);
        }

        $values = apcu_fetch($prefixedKeys);
        
        if (!is_array($values)) {
            $values = [];
        }

        $results = [];
        foreach ($keys as $key) {
            $prefixedKey = $this->getKey($key);
            $results[$key] = $values[$prefixedKey] ?? $default;
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $prefixedKeys = [];
        foreach ($keys as $key) {
            $prefixedKeys[] = $this->getKey($key);
        }

        $result = apcu_delete($prefixedKeys);
        return is_array($result) ? empty($result) : $result;
    }

    /**
     * Получить информацию о кэше
     */
    public function info(): array|false
    {
        return apcu_cache_info(true);
    }

    /**
     * Получить информацию о памяти
     */
    public function smaInfo(): array|false
    {
        return apcu_sma_info(true);
    }
}

