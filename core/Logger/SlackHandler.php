<?php declare(strict_types=1);

namespace Core\Logger;

/**
 * Slack Handler для отправки логов в Slack через Incoming Webhooks
 *
 * Для работы необходимо создать Incoming Webhook в Slack:
 * https://api.slack.com/messaging/webhooks
 */
class SlackHandler implements LogHandlerInterface
{
    protected string $webhookUrl;
    protected string $channel;
    protected string $username;
    protected string $emoji;
    protected string $minLevel;
    protected array $levels = ['debug', 'info', 'warning', 'error', 'critical'];

    /**
     * @param string $webhookUrl Webhook URL от Slack
     * @param string $channel Канал для отправки (например, #logs)
     * @param string $username Имя бота
     * @param string $emoji Эмодзи бота
     * @param string $minLevel Минимальный уровень логирования
     */
    public function __construct(
        string $webhookUrl,
        string $channel = '#logs',
        string $username = 'Logger Bot',
        string $emoji = ':robot_face:',
        string $minLevel = 'error'
    )
    {
        $this->webhookUrl = $webhookUrl;
        $this->channel = $channel;
        $this->username = $username;
        $this->emoji = $emoji;
        $this->minLevel = strtolower($minLevel);
    }

    public function handle(string $level, string $message): void
    {
        // Проверяем минимальный уровень
        if (!$this->shouldLog($level)) {
            return;
        }

        // Проверяем наличие webhook URL
        if (empty($this->webhookUrl)) {
            return;
        }

        $payload = $this->buildPayload($level, $message);
        $this->sendToSlack($payload);
    }

    /**
     * Проверяет, нужно ли логировать сообщение данного уровня
     */
    protected function shouldLog(string $level): bool
    {
        $currentLevelIndex = array_search($level, $this->levels);
        $minLevelIndex = array_search($this->minLevel, $this->levels);

        if ($currentLevelIndex === false || $minLevelIndex === false) {
            return false;
        }

        return $currentLevelIndex >= $minLevelIndex;
    }

    /**
     * Строит payload для отправки в Slack
     */
    protected function buildPayload(string $level, string $message): array
    {
        $color = $this->getColorForLevel($level);
        $emoji = $this->getEmojiForLevel($level);

        return [
            'channel' => $this->channel,
            'username' => $this->username,
            'icon_emoji' => $this->emoji,
            'attachments' => [
                [
                    'color' => $color,
                    'title' => $emoji . ' ' . strtoupper($level),
                    'text' => $message,
                    'footer' => \Core\Http::getHost(),
                    'ts' => time(),
                ]
            ]
        ];
    }

    /**
     * Получает цвет для уровня логирования
     */
    protected function getColorForLevel(string $level): string
    {
        return match ($level) {
            'debug' => '#6c757d',      // серый
            'info' => '#17a2b8',       // голубой
            'warning' => '#ffc107',    // желтый
            'error' => '#dc3545',      // красный
            'critical' => '#721c24',   // темно-красный
            default => '#6c757d',
        };
    }

    /**
     * Получает эмодзи для уровня логирования
     */
    protected function getEmojiForLevel(string $level): string
    {
        return match ($level) {
            'debug' => '🐛',
            'info' => 'ℹ️',
            'warning' => '⚠️',
            'error' => '❌',
            'critical' => '🔥',
            default => '📝',
        };
    }

    /**
     * Отправляет payload в Slack
     */
    protected function sendToSlack(array $payload): void
    {
        $ch = curl_init($this->webhookUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        // В случае ошибки можно логировать в файл, но не создаем рекурсию
        if ($httpCode !== 200 && $response !== 'ok') {
            error_log("SlackHandler: Failed to send log. HTTP Code: $httpCode");
        }
    }
}
