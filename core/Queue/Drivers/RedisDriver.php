<?php declare(strict_types=1);

namespace Core\Queue\Drivers;

use Core\Queue\QueueInterface;
use Core\Queue\Job;
use Redis;

/**
 * Драйвер для работы с Redis
 * 
 * Требует установки расширения: pecl install redis
 * Или composer require predis/predis (альтернатива)
 */
class RedisDriver implements QueueInterface
{
    protected ?Redis $redis = null;
    protected string $host;
    protected int $port;
    protected ?string $password;
    protected int $database;
    protected string $prefix = 'queue:';

    public function __construct(
        string $host = 'localhost',
        int $port = 6379,
        ?string $password = null,
        int $database = 0
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->database = $database;
    }

    /**
     * Устанавливает соединение с Redis
     */
    protected function connect(): void
    {
        if ($this->redis === null) {
            if (!extension_loaded('redis')) {
                throw new \RuntimeException(
                    'Redis extension is not installed. Install it with: pecl install redis'
                );
            }

            $this->redis = new Redis();
            
            try {
                $this->redis->connect($this->host, $this->port);
                
                if ($this->password) {
                    $this->redis->auth($this->password);
                }
                
                $this->redis->select($this->database);
            } catch (\RedisException $e) {
                throw new \RuntimeException(
                    "Failed to connect to Redis: " . $e->getMessage(),
                    0,
                    $e
                );
            }
        }
    }

    /**
     * Получает ключ для очереди
     */
    protected function getQueueKey(string $queue): string
    {
        return $this->prefix . $queue;
    }

    /**
     * Получает ключ для delayed очереди
     */
    protected function getDelayedKey(string $queue): string
    {
        return $this->prefix . $queue . ':delayed';
    }

    /**
     * Получает ключ для reserved очереди
     */
    protected function getReservedKey(string $queue): string
    {
        return $this->prefix . $queue . ':reserved';
    }

    public function push(Job $job, string $queue = 'default'): string
    {
        $this->connect();
        
        $id = uniqid('redis_', true);
        $job->setId($id);
        
        $payload = $job->serialize();
        
        $this->redis->rPush($this->getQueueKey($queue), $payload);
        
        return $id;
    }

    public function pop(string $queue = 'default'): ?Job
    {
        $this->connect();
        
        // Проверяем delayed задачи
        $this->migrateExpiredJobs($queue);
        
        // Получаем задачу из очереди
        $payload = $this->redis->lPop($this->getQueueKey($queue));
        
        if ($payload === false) {
            return null;
        }
        
        try {
            $job = Job::unserialize($payload);
            $job->incrementAttempts();
            
            // Сохраняем в reserved для возможности release
            $this->redis->hSet(
                $this->getReservedKey($queue),
                $job->getId(),
                $payload
            );
            
            return $job;
        } catch (\Exception $e) {
            // Если не удалось десериализовать, просто пропускаем
            throw $e;
        }
    }

    /**
     * Перемещает просроченные delayed задачи в основную очередь
     */
    protected function migrateExpiredJobs(string $queue): void
    {
        $now = time();
        $key = $this->getDelayedKey($queue);
        
        // Получаем просроченные задачи
        $jobs = $this->redis->zRangeByScore($key, '-inf', (string)$now);
        
        if (!empty($jobs)) {
            foreach ($jobs as $job) {
                $this->redis->rPush($this->getQueueKey($queue), $job);
            }
            
            // Удаляем перемещенные задачи из delayed
            $this->redis->zRemRangeByScore($key, '-inf', (string)$now);
        }
    }

    public function acknowledge(Job $job): void
    {
        $this->connect();
        
        // Удаляем из reserved
        $this->redis->hDel($this->getReservedKey('default'), $job->getId());
    }

    public function release(Job $job, int $delay = 0): void
    {
        $this->connect();
        
        $payload = $this->redis->hGet($this->getReservedKey('default'), $job->getId());
        
        if ($payload) {
            if ($delay > 0) {
                // Добавляем в delayed очередь с таймстампом
                $this->redis->zAdd(
                    $this->getDelayedKey('default'),
                    time() + $delay,
                    $payload
                );
            } else {
                // Возвращаем в начало очереди
                $this->redis->lPush($this->getQueueKey('default'), $payload);
            }
            
            // Удаляем из reserved
            $this->redis->hDel($this->getReservedKey('default'), $job->getId());
        }
    }

    public function delete(Job $job): void
    {
        $this->acknowledge($job);
    }

    public function size(string $queue = 'default'): int
    {
        $this->connect();
        return (int)$this->redis->lLen($this->getQueueKey($queue));
    }

    public function clear(string $queue = 'default'): void
    {
        $this->connect();
        
        $this->redis->del($this->getQueueKey($queue));
        $this->redis->del($this->getDelayedKey($queue));
        $this->redis->del($this->getReservedKey($queue));
    }

    /**
     * Закрывает соединение
     */
    public function __destruct()
    {
        if ($this->redis) {
            $this->redis->close();
        }
    }
}
