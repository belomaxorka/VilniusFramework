<?php declare(strict_types=1);

namespace Core\Cache;

use Core\Environment;
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

    /**
     * Статистика операций
     */
    protected array $stats = [
        'hits' => 0,
        'misses' => 0,
        'writes' => 0,
        'deletes' => 0,
        'operations' => [],
    ];

    /**
     * Включено ли логирование для debug toolbar
     */
    protected bool $debugEnabled = false;

    public function __construct(array $config = [])
    {
        $this->prefix = $config['prefix'] ?? '';
        $this->defaultTtl = $config['ttl'] ?? null;
        
        // Включаем debug только если установлен APP_DEBUG
        $this->debugEnabled = Environment::isDebug();
    }

    /**
     * Получить статистику операций
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Очистить статистику
     */
    public function clearStats(): void
    {
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'writes' => 0,
            'deletes' => 0,
            'operations' => [],
        ];
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

    /**
     * Залогировать операцию get (hit/miss)
     */
    protected function logGet(string $key, bool $hit, mixed $value = null, float $startTime = 0.0): void
    {
        if (!$this->debugEnabled) {
            return;
        }

        $time = $startTime > 0 ? (microtime(true) - $startTime) * 1000 : 0;

        if ($hit) {
            $this->stats['hits']++;
            $operation = [
                'type' => 'hit',
                'key' => $key,
                'value' => $value,
                'time' => $time,
            ];
        } else {
            $this->stats['misses']++;
            $operation = [
                'type' => 'miss',
                'key' => $key,
                'time' => $time,
            ];
        }

        $this->stats['operations'][] = $operation;

        // Логируем в CacheCollector если доступен
        if (class_exists('\Core\DebugToolbar\Collectors\CacheCollector')) {
            if ($hit) {
                \Core\DebugToolbar\Collectors\CacheCollector::logHit($key, $value, $time);
            } else {
                \Core\DebugToolbar\Collectors\CacheCollector::logMiss($key, $time);
            }
        }
    }

    /**
     * Залогировать операцию set
     */
    protected function logSet(string $key, mixed $value = null, float $startTime = 0.0): void
    {
        if (!$this->debugEnabled) {
            return;
        }

        $time = $startTime > 0 ? (microtime(true) - $startTime) * 1000 : 0;
        $this->stats['writes']++;

        $this->stats['operations'][] = [
            'type' => 'write',
            'key' => $key,
            'value' => $value,
            'time' => $time,
        ];

        // Логируем в CacheCollector если доступен
        if (class_exists('\Core\DebugToolbar\Collectors\CacheCollector')) {
            \Core\DebugToolbar\Collectors\CacheCollector::logWrite($key, $value, $time);
        }
    }

    /**
     * Залогировать операцию delete
     */
    protected function logDelete(string $key, float $startTime = 0.0): void
    {
        if (!$this->debugEnabled) {
            return;
        }

        $time = $startTime > 0 ? (microtime(true) - $startTime) * 1000 : 0;
        $this->stats['deletes']++;

        $this->stats['operations'][] = [
            'type' => 'delete',
            'key' => $key,
            'time' => $time,
        ];

        // Логируем в CacheCollector если доступен
        if (class_exists('\Core\DebugToolbar\Collectors\CacheCollector')) {
            \Core\DebugToolbar\Collectors\CacheCollector::logDelete($key, $time);
        }
    }
}

