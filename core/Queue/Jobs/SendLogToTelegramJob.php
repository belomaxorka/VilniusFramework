<?php declare(strict_types=1);

namespace Core\Queue\Jobs;

use Core\Queue\Job;

/**
 * Задача для отправки лога в Telegram
 */
class SendLogToTelegramJob extends Job
{
    protected int $maxAttempts = 3;

    public function __construct(
        protected string $botToken = '',
        protected string $chatId = '',
        protected string $parseMode = 'HTML',
        protected string $message = ''
    ) {
    }

    public function handle(): void
    {
        $url = sprintf('https://api.telegram.org/bot%s/sendMessage', $this->botToken);

        $payload = [
            'chat_id' => $this->chatId,
            'text' => $this->message,
            'parse_mode' => $this->parseMode,
            'disable_web_page_preview' => true,
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \RuntimeException(
                "Failed to send log to Telegram. HTTP Code: {$httpCode}"
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
                'botToken' => $this->botToken,
                'chatId' => $this->chatId,
                'parseMode' => $this->parseMode,
                'message' => $this->message,
            ],
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Создает задачу из сериализованных данных
     */
    public static function fromData(array $data): self
    {
        $job = new self(
            $data['botToken'] ?? '',
            $data['chatId'] ?? '',
            $data['parseMode'] ?? 'HTML',
            $data['message'] ?? ''
        );
        
        return $job;
    }
}
