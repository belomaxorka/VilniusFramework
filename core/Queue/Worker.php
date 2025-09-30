<?php declare(strict_types=1);

namespace Core\Queue;

use Core\Logger;

/**
 * Worker для обработки задач из очереди
 */
class Worker
{
    protected QueueInterface $queue;
    protected bool $shouldQuit = false;
    protected int $maxJobs = 0;
    protected int $processedJobs = 0;
    protected int $memory = 128; // MB
    protected int $timeout = 60; // seconds
    protected int $sleep = 3; // seconds

    public function __construct(?QueueInterface $queue = null)
    {
        $this->queue = $queue ?? QueueManager::getDriver();
    }

    /**
     * Запускает обработку очереди
     *
     * @param string $queueName Имя очереди
     * @param int $maxJobs Максимальное количество задач (0 = бесконечно)
     * @param int $memory Лимит памяти в MB
     * @param int $timeout Таймаут выполнения задачи в секундах
     * @param int $sleep Пауза между проверками очереди в секундах
     */
    public function work(
        string $queueName = 'default',
        int $maxJobs = 0,
        int $memory = 128,
        int $timeout = 60,
        int $sleep = 3
    ): void {
        $this->maxJobs = $maxJobs;
        $this->memory = $memory;
        $this->timeout = $timeout;
        $this->sleep = $sleep;

        echo "Worker started for queue: {$queueName}\n";
        echo "Memory limit: {$memory}MB, Timeout: {$timeout}s, Sleep: {$sleep}s\n";

        // Обработка сигналов для graceful shutdown
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'stop']);
            pcntl_signal(SIGINT, [$this, 'stop']);
        }

        while (!$this->shouldQuit) {
            // Проверяем память
            if ($this->memoryExceeded()) {
                echo "Memory limit exceeded. Stopping worker.\n";
                break;
            }

            // Проверяем лимит задач
            if ($this->maxJobs > 0 && $this->processedJobs >= $this->maxJobs) {
                echo "Max jobs limit reached. Stopping worker.\n";
                break;
            }

            // Обрабатываем сигналы
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            // Пытаемся получить задачу
            $job = $this->queue->pop($queueName);

            if ($job === null) {
                // Очередь пуста, ждем
                $this->sleep();
                continue;
            }

            // Обрабатываем задачу
            $this->process($job, $queueName);
        }

        echo "Worker stopped. Processed {$this->processedJobs} jobs.\n";
    }

    /**
     * Обрабатывает задачу
     */
    protected function process(Job $job, string $queueName): void
    {
        try {
            echo sprintf(
                "[%s] Processing job: %s (attempt %d/%d)\n",
                date('Y-m-d H:i:s'),
                get_class($job),
                $job->getAttempts(),
                $job->getMaxAttempts()
            );

            // Выполняем задачу с таймаутом
            $this->runJobWithTimeout($job);

            // Задача выполнена успешно
            $this->queue->acknowledge($job);
            $this->processedJobs++;

            echo sprintf(
                "[%s] Job completed: %s\n",
                date('Y-m-d H:i:s'),
                get_class($job)
            );

        } catch (\Exception $e) {
            $this->handleFailedJob($job, $queueName, $e);
        }
    }

    /**
     * Выполняет задачу с таймаутом
     */
    protected function runJobWithTimeout(Job $job): void
    {
        // Устанавливаем таймаут если возможно
        if (function_exists('pcntl_alarm')) {
            pcntl_alarm($this->timeout);
        }

        try {
            $job->handle();
        } finally {
            // Отключаем таймаут
            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(0);
            }
        }
    }

    /**
     * Обрабатывает неудачное выполнение задачи
     */
    protected function handleFailedJob(Job $job, string $queueName, \Exception $e): void
    {
        echo sprintf(
            "[%s] Job failed: %s - %s\n",
            date('Y-m-d H:i:s'),
            get_class($job),
            $e->getMessage()
        );

        // Логируем ошибку
        Logger::error('Queue job failed', [
            'job' => get_class($job),
            'queue' => $queueName,
            'attempts' => $job->getAttempts(),
            'error' => $e->getMessage(),
        ]);

        // Проверяем лимит попыток
        if ($job->maxAttemptsExceeded()) {
            echo sprintf(
                "[%s] Max attempts exceeded. Deleting job: %s\n",
                date('Y-m-d H:i:s'),
                get_class($job)
            );

            $this->queue->delete($job);

            // Логируем критическую ошибку
            Logger::critical('Queue job failed permanently', [
                'job' => get_class($job),
                'queue' => $queueName,
                'attempts' => $job->getAttempts(),
                'error' => $e->getMessage(),
            ]);
        } else {
            // Возвращаем в очередь с задержкой (exponential backoff)
            $delay = $this->calculateDelay($job->getAttempts());
            
            echo sprintf(
                "[%s] Releasing job back to queue with %ds delay: %s\n",
                date('Y-m-d H:i:s'),
                $delay,
                get_class($job)
            );

            $this->queue->release($job, $delay);
        }

        $this->processedJobs++;
    }

    /**
     * Вычисляет задержку для повторной попытки (exponential backoff)
     */
    protected function calculateDelay(int $attempts): int
    {
        return min(pow(2, $attempts) * 10, 300); // макс 5 минут
    }

    /**
     * Проверяет превышение лимита памяти
     */
    protected function memoryExceeded(): bool
    {
        $memoryMB = memory_get_usage(true) / 1024 / 1024;
        return $memoryMB >= $this->memory;
    }

    /**
     * Спит указанное количество секунд
     */
    protected function sleep(): void
    {
        sleep($this->sleep);
    }

    /**
     * Останавливает worker (graceful shutdown)
     */
    public function stop(): void
    {
        echo "\nReceived stop signal. Finishing current job...\n";
        $this->shouldQuit = true;
    }

    /**
     * Получает статистику worker'а
     */
    public function getStats(): array
    {
        return [
            'processed_jobs' => $this->processedJobs,
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_limit_mb' => $this->memory,
        ];
    }
}
