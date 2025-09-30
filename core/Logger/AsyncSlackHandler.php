<?php declare(strict_types=1);

namespace Core\Logger;

use Core\Queue\QueueManager;
use Core\Queue\Jobs\SendLogToSlackJob;

/**
 * Асинхронный Slack Handler - отправляет логи через очередь
 */
class AsyncSlackHandler extends SlackHandler
{
    protected string $queueName;

    public function __construct(
        string $webhookUrl,
        string $channel = '#logs',
        string $username = 'Logger Bot',
        string $emoji = ':robot_face:',
        string $minLevel = 'error',
        string $queueName = 'logs'
    ) {
        parent::__construct($webhookUrl, $channel, $username, $emoji, $minLevel);
        $this->queueName = $queueName;
    }

    /**
     * Переопределяем метод отправки - используем очередь
     */
    protected function sendToSlack(array $payload): void
    {
        $job = new SendLogToSlackJob(
            $this->webhookUrl,
            $this->channel,
            $this->username,
            $this->emoji,
            $payload
        );

        try {
            QueueManager::push($job, $this->queueName);
        } catch (\Exception $e) {
            // Если не удалось добавить в очередь, логируем ошибку
            error_log("AsyncSlackHandler: Failed to queue log: " . $e->getMessage());
        }
    }
}
