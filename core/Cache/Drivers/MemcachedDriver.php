<?php declare(strict_types=1);

namespace Core\Cache\Drivers;

use Core\Cache\AbstractCacheDriver;
use Core\Cache\Exceptions\CacheException;
use DateInterval;
use Memcached;

/**
 * Memcached cache driver
 */
class MemcachedDriver extends AbstractCacheDriver
{
    protected Memcached $memcached;
    protected array $servers;
    protected array $options;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        if (!extension_loaded('memcached')) {
            throw new CacheException('Memcached extension is not loaded');
        }

        $this->servers = $config['servers'] ?? [
            ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 100]
        ];
        
        $this->options = $config['options'] ?? [];

        $this->connect();
    }

    /**
     * Подключиться к Memcached
     */
    protected function connect(): void
    {
        $this->memcached = new Memcached();

        // Устанавливаем опции
        $defaultOptions = [
            Memcached::OPT_COMPRESSION => true,
            Memcached::OPT_SERIALIZER => Memcached::SERIALIZER_PHP,
            Memcached::OPT_BINARY_PROTOCOL => true,
            Memcached::OPT_TCP_NODELAY => true,
            Memcached::OPT_CONNECT_TIMEOUT => 2000,
        ];

        foreach (array_merge($defaultOptions, $this->options) as $option => $value) {
            $this->memcached->setOption($option, $value);
        }

        // Добавляем серверы
        foreach ($this->servers as $server) {
            $this->memcached->addServer(
                $server['host'] ?? '127.0.0.1',
                $server['port'] ?? 11211,
                $server['weight'] ?? 0
            );
        }

        // Проверяем соединение
        $stats = $this->memcached->getStats();
        if (empty($stats)) {
            throw new CacheException('Could not connect to Memcached server');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->getKey($key);
        $value = $this->memcached->get($key);

        if ($this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
            return $default;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        $key = $this->getKey($key);
        $ttl = $this->normalizeTtl($ttl) ?? 0;

        // Memcached использует Unix timestamp для TTL > 30 дней
        if ($ttl > 2592000) { // 30 дней в секундах
            $ttl = time() + $ttl;
        }

        return $this->memcached->set($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $key = $this->getKey($key);
        $result = $this->memcached->delete($key);
        
        // Считаем успехом, если ключ не найден
        return $result || $this->memcached->getResultCode() === Memcached::RES_NOTFOUND;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return $this->memcached->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $key = $this->getKey($key);
        $this->memcached->get($key);
        
        return $this->memcached->getResultCode() !== Memcached::RES_NOTFOUND;
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $key, int $value = 1): int|false
    {
        $key = $this->getKey($key);
        $result = $this->memcached->increment($key, $value);

        if ($result === false && $this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
            // Если ключ не существует, создаем его
            $this->memcached->set($key, $value, 0);
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
        $result = $this->memcached->decrement($key, $value);

        if ($result === false && $this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
            // Если ключ не существует, создаем его
            $this->memcached->set($key, -$value, 0);
            return -$value;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        $key = $this->getKey($key);
        $ttl = $this->normalizeTtl($ttl) ?? 0;

        if ($ttl > 2592000) {
            $ttl = time() + $ttl;
        }

        return $this->memcached->add($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $prefixedKeys = [];
        $keyMap = [];
        
        foreach ($keys as $key) {
            $prefixedKey = $this->getKey($key);
            $prefixedKeys[] = $prefixedKey;
            $keyMap[$prefixedKey] = $key;
        }

        $values = $this->memcached->getMulti($prefixedKeys);
        
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
    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        $ttl = $this->normalizeTtl($ttl) ?? 0;

        if ($ttl > 2592000) {
            $ttl = time() + $ttl;
        }

        $prefixedValues = [];
        foreach ($values as $key => $value) {
            $prefixedValues[$this->getKey($key)] = $value;
        }

        return $this->memcached->setMulti($prefixedValues, $ttl);
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

        $results = $this->memcached->deleteMulti($prefixedKeys);
        
        // Проверяем, что все операции прошли успешно
        foreach ($results as $result) {
            if (!$result) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Получить статистику серверов
     */
    public function getStats(): array|false
    {
        return $this->memcached->getStats();
    }

    /**
     * Получить версию серверов
     */
    public function getVersion(): array|false
    {
        return $this->memcached->getVersion();
    }

    /**
     * Получить последний код результата
     */
    public function getResultCode(): int
    {
        return $this->memcached->getResultCode();
    }

    /**
     * Получить последнее сообщение об ошибке
     */
    public function getResultMessage(): string
    {
        return $this->memcached->getResultMessage();
    }

    /**
     * Получить экземпляр Memcached
     */
    public function getMemcached(): Memcached
    {
        return $this->memcached;
    }

    /**
     * Закрыть соединения
     */
    public function disconnect(): void
    {
        $this->memcached->quit();
    }

    public function __destruct()
    {
        if (isset($this->memcached)) {
            $this->memcached->quit();
        }
    }
}

