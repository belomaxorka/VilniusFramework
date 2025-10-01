<?php declare(strict_types=1);

namespace Core\Logger;

/**
 * Telegram Handler для отправки логов в Telegram через Bot API
 *
 * Для работы необходимо:
 * 1. Создать бота через @BotFather и получить Bot Token
 * 2. Получить Chat ID (можно через @userinfobot или @getmyid_bot)
 */
class TelegramHandler implements LogHandlerInterface
{
    protected string $botToken;
    protected string $chatId;
    protected string $parseMode;
    protected string $minLevel;
    protected array $levels = ['debug', 'info', 'warning', 'error', 'critical'];

    /**
     * @param string $botToken Токен бота от @BotFather
     * @param string $chatId ID чата для отправки сообщений
     * @param string $parseMode Режим парсинга (HTML или Markdown)
     * @param string $minLevel Минимальный уровень логирования
     */
    public function __construct(
        string $botToken,
        string $chatId,
        string $parseMode = 'HTML',
        string $minLevel = 'error'
    )
    {
        $this->botToken = $botToken;
        $this->chatId = $chatId;
        $this->parseMode = $parseMode;
        $this->minLevel = strtolower($minLevel);
    }

    public function handle(string $level, string $message): void
    {
        // Проверяем минимальный уровень
        if (!$this->shouldLog($level)) {
            return;
        }

        // Проверяем наличие токена и chat ID
        if (empty($this->botToken) || empty($this->chatId)) {
            return;
        }

        $formattedMessage = $this->formatMessage($level, $message);
        $this->sendToTelegram($formattedMessage);
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
     * Форматирует сообщение для Telegram
     */
    protected function formatMessage(string $level, string $message): string
    {
        $emoji = $this->getEmojiForLevel($level);
        $levelText = strtoupper($level);
        $timestamp = date('Y-m-d H:i:s');
        $host = \Core\Http::getHost();

        if ($this->parseMode === 'HTML') {
            return sprintf(
                "%s <b>%s</b>\n\n%s\n\n<i>%s | %s</i>",
                $emoji,
                $levelText,
                htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
                $host,
                $timestamp
            );
        } else {
            // Markdown format
            return sprintf(
                "%s *%s*\n\n%s\n\n_%s | %s_",
                $emoji,
                $levelText,
                $this->escapeMarkdown($message),
                $host,
                $timestamp
            );
        }
    }

    /**
     * Экранирует специальные символы для Markdown
     */
    protected function escapeMarkdown(string $text): string
    {
        $specialChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        foreach ($specialChars as $char) {
            $text = str_replace($char, '\\' . $char, $text);
        }
        return $text;
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
     * Отправляет сообщение в Telegram
     */
    protected function sendToTelegram(string $message): void
    {
        $url = sprintf('https://api.telegram.org/bot%s/sendMessage', $this->botToken);

        $payload = [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => $this->parseMode,
            'disable_web_page_preview' => true,
        ];

        $ch = curl_init($url);

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
        if ($httpCode !== 200) {
            error_log("TelegramHandler: Failed to send log. HTTP Code: $httpCode");
            if ($response) {
                error_log("TelegramHandler Response: " . $response);
            }
        }
    }
}
