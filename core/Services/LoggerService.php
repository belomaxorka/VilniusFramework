<?php declare(strict_types=1);

namespace Core\Services;

use Core\Contracts\LoggerInterface;
use Core\Contracts\ConfigInterface;
use Core\Logger\LogHandlerInterface;
use Core\Logger\FileHandler;
use Core\Logger\SlackHandler;
use Core\Logger\TelegramHandler;

/**
 * Logger Service
 * 
 * Instance-based реализация для логирования с внедрением зависимостей
 */
class LoggerService implements LoggerInterface
{
    protected array $handlers = [];
    protected string $minLevel = 'debug';
    protected array $levels = ['debug', 'info', 'warning', 'error', 'critical'];
    protected bool $initialized = false;
    protected array $logs = [];

    public function __construct(
        private ConfigInterface $config
    ) {}

    public function init(): void
    {
        if ($this->initialized) {
            return;
        }

        $config = $this->config->get('logging', []);

        if (empty($config)) {
            // Fallback: создаем базовый file handler
            $this->addHandler(new FileHandler(LOG_DIR . '/app.log'));
            $this->initialized = true;
            return;
        }

        // Устанавливаем минимальный уровень
        $minLevel = $config['min_level'] ?? 'debug';
        $this->setMinLevel($minLevel);

        // Определяем какие каналы использовать
        $channels = $this->parseChannels($config['channels'] ?? $config['default'] ?? 'file');

        // Инициализируем драйверы для каждого канала
        foreach ($channels as $channel) {
            $driver = $this->createDriver($channel, $config['drivers'][$channel] ?? []);
            if ($driver !== null) {
                $this->addHandler($driver);
            }
        }

        $this->initialized = true;
    }

    protected function parseChannels($channels): array
    {
        if (is_array($channels)) {
            return $channels;
        }

        return array_map('trim', explode(',', (string)$channels));
    }

    protected function createDriver(string $name, array $config): ?LogHandlerInterface
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
                        return null;
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
                        return null;
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
            error_log("Failed to create logger driver '$name': " . $e->getMessage());
            return null;
        }
    }

    public function addHandler(LogHandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }

    public function log(string $level, string $message, array $context = []): void
    {
        // Автоинициализация если еще не выполнена
        if (!$this->initialized) {
            $this->init();
        }

        $level = strtolower($level);
        if (!in_array($level, $this->levels)) {
            $level = 'info';
        }
        if (array_search($level, $this->levels) < array_search($this->minLevel, $this->levels)) {
            return;
        }

        $toolbarMessage = $context['_toolbar_message'] ?? $message;
        
        $cleanContext = $context;
        unset($cleanContext['_toolbar_message']);

        // Сохраняем в памяти для Debug Toolbar
        $this->logs[] = [
            'level' => $level,
            'message' => $toolbarMessage,
            'context' => $cleanContext,
            'time' => microtime(true),
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        // Интерполируем для handlers
        $interpolatedMessage = $this->interpolate($message, $cleanContext);

        // Отправляем в handlers
        foreach ($this->handlers as $handler) {
            $handler->handle($level, $interpolatedMessage);
        }
    }

    protected function interpolate(string $message, array $context = []): string
    {
        if (empty($context)) {
            return $message;
        }

        $replace = [];
        foreach ($context as $key => $val) {
            if (is_array($val) || is_object($val)) {
                $val = json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            $replace['{' . $key . '}'] = $val;
        }

        return strtr($message, $replace);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function setMinLevel(string $level): void
    {
        $this->minLevel = strtolower($level);
    }

    public function getMinLevel(): string
    {
        return $this->minLevel;
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function getStats(): array
    {
        $stats = [
            'total' => count($this->logs),
            'by_level' => [],
        ];

        foreach ($this->levels as $level) {
            $stats['by_level'][$level] = 0;
        }

        foreach ($this->logs as $log) {
            $stats['by_level'][$log['level']]++;
        }

        return $stats;
    }

    public function clearLogs(): void
    {
        $this->logs = [];
    }
}

