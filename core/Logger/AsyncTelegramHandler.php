<?php declare(strict_types=1);

namespace Core\Logger;

use Core\Queue\QueueManager;
use Core\Queue\Jobs\SendLogToTelegramJob;

/**
 * Асинхронный Telegram Handler - отправляет логи через очередь
 */
class AsyncTelegramHandler extends TelegramHandler
{
    protected string $queueName;

    public function __construct(
        string $botToken,
        string $chatId,
        string $parseMode = 'HTML',
        string $minLevel = 'error',
        string $queueName = 'logs'
    ) {
        parent::__construct($botToken, $chatId, $parseMode, $minLevel);
        $this->queueName = $queueName;
    }

    /**
     * Переопределяем метод отправки - используем очередь
     */
    protected function sendToTelegram(string $message): void
    {
        $job = new SendLogToTelegramJob(
            $this->botToken,
            $this->chatId,
            $this->parseMode,
            $message
        );

        try {
            QueueManager::push($job, $this->queueName);
        } catch (\Exception $e) {
            // Если не удалось добавить в очередь, логируем ошибку
            error_log("AsyncTelegramHandler: Failed to queue log: " . $e->getMessage());
        }
    }
}
