<?php declare(strict_types=1);

namespace Core\Queue\Drivers;

use Core\Queue\QueueInterface;
use Core\Queue\Job;

/**
 * Синхронный драйвер - выполняет задачи сразу же
 * Используется для локальной разработки и тестирования
 */
class SyncDriver implements QueueInterface
{
    public function push(Job $job, string $queue = 'default'): string
    {
        // Генерируем ID
        $id = uniqid('sync_', true);
        $job->setId($id);

        // Сразу выполняем задачу
        try {
            $job->handle();
        } catch (\Exception $e) {
            // Логируем ошибку, но не бросаем исключение
            error_log("SyncDriver: Job execution failed: " . $e->getMessage());
        }

        return $id;
    }

    public function pop(string $queue = 'default'): ?Job
    {
        // Синхронный драйвер не хранит задачи
        return null;
    }

    public function acknowledge(Job $job): void
    {
        // Ничего не делаем в синхронном режиме
    }

    public function release(Job $job, int $delay = 0): void
    {
        // Ничего не делаем в синхронном режиме
    }

    public function delete(Job $job): void
    {
        // Ничего не делаем в синхронном режиме
    }

    public function size(string $queue = 'default'): int
    {
        return 0;
    }

    public function clear(string $queue = 'default'): void
    {
        // Ничего не делаем в синхронном режиме
    }
}
