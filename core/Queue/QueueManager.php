<?php declare(strict_types=1);

namespace Core\Queue;

use Core\Config;

/**
 * Менеджер очередей - фасад для работы с разными драйверами
 */
class QueueManager
{
    protected static ?QueueInterface $driver = null;
    protected static array $config = [];

    /**
     * Инициализация менеджера очередей
     */
    public static function init(): void
    {
        self::$config = Config::get('queue', []);
        
        if (empty(self::$config)) {
            // Fallback на синхронный драйвер
            self::$config = [
                'default' => 'sync',
                'connections' => [
                    'sync' => ['driver' => 'sync']
                ]
            ];
        }

        self::$driver = self::createDriver();
    }

    /**
     * Создает драйвер на основе конфигурации
     */
    protected static function createDriver(): QueueInterface
    {
        $connection = self::$config['default'] ?? 'sync';
        $connectionConfig = self::$config['connections'][$connection] ?? [];
        $driverName = $connectionConfig['driver'] ?? 'sync';

        return match ($driverName) {
            'sync' => new Drivers\SyncDriver(),
            'database' => new Drivers\DatabaseDriver(
                $connectionConfig['table'] ?? 'jobs'
            ),
            'rabbitmq' => new Drivers\RabbitMQDriver(
                $connectionConfig['host'] ?? 'localhost',
                $connectionConfig['port'] ?? 5672,
                $connectionConfig['user'] ?? 'guest',
                $connectionConfig['password'] ?? 'guest',
                $connectionConfig['vhost'] ?? '/'
            ),
            'redis' => new Drivers\RedisDriver(
                $connectionConfig['host'] ?? 'localhost',
                $connectionConfig['port'] ?? 6379,
                $connectionConfig['password'] ?? null,
                $connectionConfig['database'] ?? 0
            ),
            default => new Drivers\SyncDriver(),
        };
    }

    /**
     * Получает текущий драйвер
     */
    public static function getDriver(): QueueInterface
    {
        if (self::$driver === null) {
            self::init();
        }

        return self::$driver;
    }

    /**
     * Устанавливает драйвер (для тестирования)
     */
    public static function setDriver(QueueInterface $driver): void
    {
        self::$driver = $driver;
    }

    /**
     * Добавляет задачу в очередь
     */
    public static function push(Job $job, string $queue = 'default'): string
    {
        return self::getDriver()->push($job, $queue);
    }

    /**
     * Извлекает задачу из очереди
     */
    public static function pop(string $queue = 'default'): ?Job
    {
        return self::getDriver()->pop($queue);
    }

    /**
     * Помечает задачу как выполненную
     */
    public static function acknowledge(Job $job): void
    {
        self::getDriver()->acknowledge($job);
    }

    /**
     * Возвращает задачу в очередь
     */
    public static function release(Job $job, int $delay = 0): void
    {
        self::getDriver()->release($job, $delay);
    }

    /**
     * Удаляет задачу
     */
    public static function delete(Job $job): void
    {
        self::getDriver()->delete($job);
    }

    /**
     * Получает размер очереди
     */
    public static function size(string $queue = 'default'): int
    {
        return self::getDriver()->size($queue);
    }

    /**
     * Очищает очередь
     */
    public static function clear(string $queue = 'default'): void
    {
        self::getDriver()->clear($queue);
    }
}
