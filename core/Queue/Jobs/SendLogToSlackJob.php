<?php declare(strict_types=1);

namespace Core\Queue\Jobs;

use Core\Queue\Job;

/**
 * Задача для отправки лога в Slack
 */
class SendLogToSlackJob extends Job
{
    protected int $maxAttempts = 3;

    public function __construct(
        protected string $webhookUrl = '',
        protected string $channel = '',
        protected string $username = '',
        protected string $emoji = '',
        protected array $payload = []
    ) {
    }

    public function handle(): void
    {
        $ch = curl_init($this->webhookUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($this->payload),
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode !== 200 && $response !== 'ok') {
            throw new \RuntimeException(
                "Failed to send log to Slack. HTTP Code: {$httpCode}"
            );
        }
    }

    /**
     * Переопределяем сериализацию для сохранения параметров
     */
    public function serialize(): string
    {
        return json_encode([
            'class' => get_class($this),
            'id' => $this->id,
            'attempts' => $this->attempts,
            'maxAttempts' => $this->maxAttempts,
            'data' => [
                'webhookUrl' => $this->webhookUrl,
                'channel' => $this->channel,
                'username' => $this->username,
                'emoji' => $this->emoji,
                'payload' => $this->payload,
            ],
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Создает задачу из сериализованных данных
     */
    public static function fromData(array $data): self
    {
        $job = new self(
            $data['webhookUrl'] ?? '',
            $data['channel'] ?? '',
            $data['username'] ?? '',
            $data['emoji'] ?? '',
            $data['payload'] ?? []
        );
        
        return $job;
    }
}
