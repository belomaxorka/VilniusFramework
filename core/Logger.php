<?php declare(strict_types=1);

namespace Core;

use Core\Logger\LogHandlerInterface;
use Core\Logger\FileHandler;
use Core\Logger\SlackHandler;
use Core\Logger\TelegramHandler;

class Logger
{
    protected static array $handlers = [];
    protected static string $minLevel = 'debug';
    protected static array $levels = ['debug', 'info', 'warning', 'error', 'critical'];
    protected static bool $initialized = false;
    protected static array $logs = []; // Логи текущего запроса для Debug Toolbar

    /**
     * Инициализация логгера из конфигурации
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        $config = Config::get('logging', []);

        if (empty($config)) {
            // Fallback: создаем базовый file handler
            self::addHandler(new FileHandler(LOG_DIR . '/app.log'));
            self::$initialized = true;
            return;
        }

        // Устанавливаем минимальный уровень
        $minLevel = $config['min_level'] ?? 'debug';
        self::setMinLevel($minLevel);

        // Определяем какие каналы использовать
        $channels = self::parseChannels($config['channels'] ?? $config['default'] ?? 'file');

        // Инициализируем драйверы для каждого канала
        foreach ($channels as $channel) {
            $driver = self::createDriver($channel, $config['drivers'][$channel] ?? []);
            if ($driver !== null) {
                self::addHandler($driver);
            }
        }

        self::$initialized = true;
    }

    /**
     * Парсит строку каналов в массив
     */
    protected static function parseChannels($channels): array
    {
        if (is_array($channels)) {
            return $channels;
        }

        // Преобразуем строку "file,slack,telegram" в массив
        return array_map('trim', explode(',', (string)$channels));
    }

    /**
     * Создает драйвер на основе конфигурации
     */
    protected static function createDriver(string $name, array $config): ?LogHandlerInterface
    {
        $driver = $config['driver'] ?? $name;

        try {
            switch ($driver) {
                case 'file':
                    return new FileHandler(
                        $config['path'] ?? LOG_DIR . '/app.log'
                    );

                case 'slack':
                    $webhookUrl = $config['webhook_url'] ?? '';
                    if (empty($webhookUrl)) {
                        return null; // Slack webhook не настроен
                    }
                    return new SlackHandler(
                        $webhookUrl,
                        $config['channel'] ?? '#logs',
                        $config['username'] ?? 'Logger Bot',
                        $config['emoji'] ?? ':robot_face:',
                        $config['min_level'] ?? 'error'
                    );

                case 'telegram':
                    $botToken = $config['bot_token'] ?? '';
                    $chatId = $config['chat_id'] ?? '';
                    if (empty($botToken) || empty($chatId)) {
                        return null; // Telegram не настроен
                    }
                    return new TelegramHandler(
                        $botToken,
                        $chatId,
                        $config['parse_mode'] ?? 'HTML',
                        $config['min_level'] ?? 'error'
                    );

                default:
                    return null;
            }
        } catch (\Exception $e) {
            // В случае ошибки создания драйвера, просто пропускаем его
            error_log("Failed to create logger driver '$name': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Добавляет обработчик логов
     */
    public static function addHandler(LogHandlerInterface $handler): void
    {
        self::$handlers[] = $handler;
    }

    /**
     * Логирует сообщение с контекстом
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $level = strtolower($level);
        if (!in_array($level, self::$levels)) {
            $level = 'info';
        }
        if (array_search($level, self::$levels) < array_search(self::$minLevel, self::$levels)) {
            return;
        }

        // Интерполируем контекст в сообщение
        $interpolatedMessage = self::interpolate($message, $context);

        // Сохраняем в памяти для Debug Toolbar (с интерполированным сообщением)
        self::$logs[] = [
            'level' => $level,
            'message' => $interpolatedMessage,
            'context' => $context,
            'time' => microtime(true),
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        // Отправляем в handlers
        foreach (self::$handlers as $handler) {
            $handler->handle($level, $interpolatedMessage);
        }
    }

    /**
     * Интерполирует контекстные значения в сообщение
     * Пример: "User {username} logged in" с контекстом ['username' => 'John']
     */
    protected static function interpolate(string $message, array $context = []): string
    {
        if (empty($context)) {
            return $message;
        }

        $replace = [];
        foreach ($context as $key => $val) {
            // Преобразуем массивы и объекты в JSON
            if (is_array($val) || is_object($val)) {
                $val = json_encode($val);
            }
            $replace['{' . $key . '}'] = $val;
        }

        return strtr($message, $replace);
    }

    /**
     * Логирование уровня DEBUG
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log('debug', $message, $context);
    }

    /**
     * Логирование уровня INFO
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    /**
     * Логирование уровня WARNING
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    /**
     * Логирование уровня ERROR
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    /**
     * Логирование критических ошибок
     */
    public static function critical(string $message, array $context = []): void
    {
        self::log('critical', $message, $context);
    }

    /**
     * Устанавливает минимальный уровень логирования
     */
    public static function setMinLevel(string $level): void
    {
        self::$minLevel = strtolower($level);
    }

    /**
     * Получает текущий минимальный уровень
     */
    public static function getMinLevel(): string
    {
        return self::$minLevel;
    }

    /**
     * Очищает все обработчики (полезно для тестирования)
     */
    public static function clearHandlers(): void
    {
        self::$handlers = [];
        self::$initialized = false;
    }

    /**
     * Получает список обработчиков (для тестирования)
     */
    public static function getHandlers(): array
    {
        return self::$handlers;
    }

    /**
     * Получает все логи текущего запроса
     */
    public static function getLogs(): array
    {
        return self::$logs;
    }

    /**
     * Получает статистику по логам
     */
    public static function getStats(): array
    {
        $stats = [
            'total' => count(self::$logs),
            'by_level' => [],
        ];

        foreach (self::$levels as $level) {
            $stats['by_level'][$level] = 0;
        }

        foreach (self::$logs as $log) {
            $stats['by_level'][$log['level']]++;
        }

        return $stats;
    }

    /**
     * Очищает логи (для тестирования)
     */
    public static function clearLogs(): void
    {
        self::$logs = [];
    }
}
