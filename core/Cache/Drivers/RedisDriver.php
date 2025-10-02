<?php declare(strict_types=1);

namespace Core\Cache\Drivers;

use Core\Cache\AbstractCacheDriver;
use Core\Cache\Exceptions\CacheException;
use DateInterval;
use Redis;

/**
 * Redis cache driver
 */
class RedisDriver extends AbstractCacheDriver
{
    protected Redis $redis;
    protected string $host;
    protected int $port;
    protected ?string $password;
    protected int $database;
    protected float $timeout;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        if (!extension_loaded('redis')) {
            throw new CacheException('Redis extension is not loaded');
        }

        $this->host = $config['host'] ?? '127.0.0.1';
        $this->port = $config['port'] ?? 6379;
        $this->password = $config['password'] ?? null;
        $this->database = $config['database'] ?? 0;
        $this->timeout = $config['timeout'] ?? 2.5;

        $this->connect();
    }

    /**
     * Подключиться к Redis
     */
    protected function connect(): void
    {
        $this->redis = new Redis();

        try {
            $connected = $this->redis->connect(
                $this->host,
                $this->port,
                $this->timeout
            );

            if (!$connected) {
                throw new CacheException("Could not connect to Redis server at {$this->host}:{$this->port}");
            }

            if ($this->password !== null) {
                if (!$this->redis->auth($this->password)) {
                    throw new CacheException('Redis authentication failed');
                }
            }

            if ($this->database > 0) {
                $this->redis->select($this->database);
            }

            // Устанавливаем опции сериализации
            $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        } catch (\RedisException $e) {
            throw new CacheException('Redis connection error: ' . $e->getMessage(), 0, $e);
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
        $value = $this->redis->get($key);

        $result = $value !== false ? $value : $default;
        $this->logGet($originalKey, $value !== false, $result, $startTime);
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        $startTime = microtime(true);
        $originalKey = $key;
        $key = $this->getKey($key);
        $ttl = $this->normalizeTtl($ttl);

        if ($ttl === null) {
            $result = $this->redis->set($key, $value);
        } else {
            $result = $this->redis->setex($key, $ttl, $value);
        }

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
        $result = $this->redis->del($key) > 0;
        $this->logDelete($originalKey, $startTime);
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return $this->redis->flushDB();
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $key = $this->getKey($key);
        return $this->redis->exists($key) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $key, int $value = 1): int|false
    {
        $key = $this->getKey($key);
        return $this->redis->incrBy($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(string $key, int $value = 1): int|false
    {
        $key = $this->getKey($key);
        return $this->redis->decrBy($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        $key = $this->getKey($key);
        $ttl = $this->normalizeTtl($ttl);

        if ($ttl === null) {
            return $this->redis->setnx($key, $value);
        }

        // setnx с TTL
        return $this->redis->set($key, $value, ['nx', 'ex' => $ttl]);
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

        $values = $this->redis->mget($prefixedKeys);
        
        $results = [];
        foreach ($prefixedKeys as $index => $prefixedKey) {
            $originalKey = $keyMap[$prefixedKey];
            $results[$originalKey] = $values[$index] !== false ? $values[$index] : $default;
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        $ttl = $this->normalizeTtl($ttl);
        
        $pipeline = $this->redis->multi(Redis::PIPELINE);
        
        foreach ($values as $key => $value) {
            $key = $this->getKey($key);
            
            if ($ttl === null) {
                $pipeline->set($key, $value);
            } else {
                $pipeline->setex($key, $ttl, $value);
            }
        }
        
        $results = $pipeline->exec();
        
        // Проверяем, что все операции прошли успешно
        foreach ($results as $result) {
            if (!$result) {
                return false;
            }
        }
        
        return true;
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

        return $this->redis->del($prefixedKeys) > 0;
    }

    /**
     * Получить TTL ключа
     */
    public function ttl(string $key): int
    {
        $key = $this->getKey($key);
        return $this->redis->ttl($key);
    }

    /**
     * Получить информацию о Redis
     */
    public function info(): array
    {
        return $this->redis->info();
    }

    /**
     * Выполнить команду Redis
     */
    public function command(string $command, array $args = []): mixed
    {
        return $this->redis->rawCommand($command, ...$args);
    }

    /**
     * Получить экземпляр Redis
     */
    public function getRedis(): Redis
    {
        return $this->redis;
    }

    /**
     * Закрыть соединение
     */
    public function disconnect(): void
    {
        $this->redis->close();
    }

    public function __destruct()
    {
        if (isset($this->redis)) {
            $this->redis->close();
        }
    }
}

