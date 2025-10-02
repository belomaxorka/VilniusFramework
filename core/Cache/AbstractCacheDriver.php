<?php declare(strict_types=1);

namespace Core\Cache;

use DateInterval;

abstract class AbstractCacheDriver implements CacheDriverInterface
{
    /**
     * Префикс для ключей
     */
    protected string $prefix = '';

    /**
     * Время жизни кэша по умолчанию (в секундах)
     */
    protected ?int $defaultTtl = null;

    public function __construct(array $config = [])
    {
        $this->prefix = $config['prefix'] ?? '';
        $this->defaultTtl = $config['ttl'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $results = [];

        foreach ($keys as $key) {
            $results[$key] = $this->get($key, $default);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;

        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->delete($key);
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        if ($this->has($key)) {
            return false;
        }

        return $this->set($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function forever(string $key, mixed $value): bool
    {
        return $this->set($key, $value, null);
    }

    /**
     * {@inheritdoc}
     */
    public function remember(string $key, int|DateInterval|null $ttl, \Closure $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function rememberForever(string $key, \Closure $callback): mixed
    {
        return $this->remember($key, null, $callback);
    }

    /**
     * Получить полное имя ключа с префиксом
     */
    protected function getKey(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * Нормализовать TTL к секундам
     */
    protected function normalizeTtl(int|DateInterval|null $ttl): ?int
    {
        if ($ttl === null) {
            return $this->defaultTtl;
        }

        if ($ttl instanceof DateInterval) {
            return (new \DateTime())->add($ttl)->getTimestamp() - time();
        }

        return $ttl;
    }

    /**
     * Сериализовать значение
     */
    protected function serialize(mixed $value): string
    {
        return serialize($value);
    }

    /**
     * Десериализовать значение
     */
    protected function unserialize(string $value): mixed
    {
        return unserialize($value);
    }
}

