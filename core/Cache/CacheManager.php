<?php declare(strict_types=1);

namespace Core\Cache;

use Core\Cache\Drivers\ApcuDriver;
use Core\Cache\Drivers\ArrayDriver;
use Core\Cache\Drivers\FileDriver;
use Core\Cache\Drivers\MemcachedDriver;
use Core\Cache\Drivers\RedisDriver;
use Core\Cache\Exceptions\CacheException;

class CacheManager
{
    protected array $config;
    protected array $drivers = [];
    protected array $driverRegistry = [
        'array' => ArrayDriver::class,
        'file' => FileDriver::class,
        'apcu' => ApcuDriver::class,
        'redis' => RedisDriver::class,
        'memcached' => MemcachedDriver::class,
    ];
    protected ?string $defaultDriver = null;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->defaultDriver = $config['default'] ?? 'array';
    }

    /**
     * Получить драйвер кэша
     */
    public function driver(?string $name = null): CacheDriverInterface
    {
        $name = $name ?: $this->defaultDriver;

        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }

        return $this->drivers[$name];
    }

    /**
     * Создать драйвер кэша
     */
    protected function createDriver(string $name): CacheDriverInterface
    {
        if (!isset($this->config['stores'][$name])) {
            throw new CacheException("Cache store [{$name}] is not configured.");
        }

        $config = $this->config['stores'][$name];
        $driverName = $config['driver'];

        if (!isset($this->driverRegistry[$driverName])) {
            throw new CacheException("Cache driver [{$driverName}] is not supported.");
        }

        $driverClass = $this->driverRegistry[$driverName];

        return new $driverClass($config);
    }

    /**
     * Добавить кастомный драйвер
     */
    public function extend(string $name, string $driverClass): void
    {
        if (!class_exists($driverClass)) {
            throw new CacheException("Driver class [{$driverClass}] does not exist.");
        }

        if (!in_array(CacheDriverInterface::class, class_implements($driverClass))) {
            throw new CacheException("Driver class must implement CacheDriverInterface.");
        }

        $this->driverRegistry[$name] = $driverClass;
    }

    /**
     * Получить конфигурацию драйвера
     */
    public function getDriverConfig(string $name): array
    {
        return $this->config['stores'][$name] ?? [];
    }

    /**
     * Получить имя драйвера по умолчанию
     */
    public function getDefaultDriver(): string
    {
        return $this->defaultDriver;
    }

    /**
     * Установить драйвер по умолчанию
     */
    public function setDefaultDriver(string $name): void
    {
        $this->defaultDriver = $name;
    }

    /**
     * Очистить все драйверы
     */
    public function purge(?string $name = null): void
    {
        if ($name === null) {
            foreach ($this->drivers as $driver) {
                $driver->clear();
            }
        } elseif (isset($this->drivers[$name])) {
            $this->drivers[$name]->clear();
        }
    }

    /**
     * Проксирование вызовов к драйверу по умолчанию
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->driver()->$method(...$parameters);
    }
}

