<?php declare(strict_types=1);

namespace Core\Queue;

/**
 * Интерфейс для работы с очередями
 */
interface QueueInterface
{
    /**
     * Добавляет задачу в очередь
     *
     * @param Job $job Задача для выполнения
     * @param string $queue Имя очереди (по умолчанию 'default')
     * @return string ID задачи в очереди
     */
    public function push(Job $job, string $queue = 'default'): string;

    /**
     * Извлекает задачу из очереди для выполнения
     *
     * @param string $queue Имя очереди
     * @return Job|null Задача или null если очередь пуста
     */
    public function pop(string $queue = 'default'): ?Job;

    /**
     * Помечает задачу как выполненную
     *
     * @param Job $job Выполненная задача
     * @return void
     */
    public function acknowledge(Job $job): void;

    /**
     * Возвращает задачу обратно в очередь (в случае ошибки)
     *
     * @param Job $job Задача
     * @param int $delay Задержка в секундах перед повторной попыткой
     * @return void
     */
    public function release(Job $job, int $delay = 0): void;

    /**
     * Удаляет задачу из очереди (при критической ошибке)
     *
     * @param Job $job Задача
     * @return void
     */
    public function delete(Job $job): void;

    /**
     * Получает размер очереди
     *
     * @param string $queue Имя очереди
     * @return int Количество задач в очереди
     */
    public function size(string $queue = 'default'): int;

    /**
     * Очищает очередь
     *
     * @param string $queue Имя очереди
     * @return void
     */
    public function clear(string $queue = 'default'): void;
}
