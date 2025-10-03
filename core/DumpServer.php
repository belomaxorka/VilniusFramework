<?php declare(strict_types=1);

namespace Core;

class DumpServer
{
    private static ?string $host = '127.0.0.1';
    private static int $port = 9912;
    private static $socket = null;
    private static bool $running = false;

    /**
     * Установить хост и порт
     */
    public static function configure(string $host = '127.0.0.1', int $port = 9912): void
    {
        self::$host = $host;
        self::$port = $port;
    }

    /**
     * Запустить сервер
     */
    public static function start(?callable $outputHandler = null): void
    {
        if (self::$running) {
            return;
        }

        $address = self::$host . ':' . self::$port;

        echo "🚀 Dump Server started on {$address}\n";
        echo "Waiting for dumps...\n\n";

        self::$socket = @stream_socket_server(
            "tcp://{$address}",
            $errno,
            $errstr,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
        );

        if (!self::$socket) {
            throw new \RuntimeException("Failed to start server: {$errstr} ({$errno})");
        }

        self::$running = true;

        // Обработка SIGINT для корректного завершения
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, function () {
                self::stop();
                exit(0);
            });
        }

        while (self::$running) {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            $client = @stream_socket_accept(self::$socket, 1);

            if ($client) {
                $data = stream_get_contents($client);
                fclose($client);

                if ($data) {
                    $decoded = json_decode($data, true);

                    if ($decoded) {
                        if ($outputHandler) {
                            $outputHandler($decoded);
                        } else {
                            self::defaultOutput($decoded);
                        }
                    }
                }
            }
        }

        self::stop();
    }

    /**
     * Остановить сервер
     */
    public static function stop(): void
    {
        if (self::$socket) {
            fclose(self::$socket);
            self::$socket = null;
        }

        self::$running = false;

        echo "\n👋 Dump Server stopped\n";
    }

    /**
     * Проверить доступность сервера
     */
    public static function isAvailable(): bool
    {
        $connection = @stream_socket_client(
            "tcp://" . self::$host . ":" . self::$port,
            $errno,
            $errstr,
            1
        );

        if ($connection) {
            fclose($connection);
            return true;
        }

        return false;
    }

    /**
     * Получить конфигурацию
     */
    public static function getConfig(): array
    {
        return [
            'host' => self::$host,
            'port' => self::$port,
        ];
    }

    /**
     * Вывод по умолчанию
     */
    private static function defaultOutput(array $data): void
    {
        $timestamp = date('H:i:s');
        $type = $data['type'] ?? 'dump';
        $label = $data['label'] ?? null;
        $content = $data['content'] ?? '';
        $dataType = $data['data_type'] ?? 'unknown';
        $file = $data['file'] ?? 'unknown';
        $line = $data['line'] ?? 0;

        // Заголовок
        echo str_repeat('─', 80) . "\n";
        echo "⏰ {$timestamp} ";

        if ($label) {
            echo "📝 {$label} ";
        }

        // Показываем относительный путь (убираем корень проекта)
        $relativePath = str_replace([ROOT . '/', ROOT . '\\'], '', $file);
        $relativePath = str_replace('\\', '/', $relativePath); // Нормализуем слеши
        echo "📍 {$relativePath}:{$line}\n";
        echo str_repeat('─', 80) . "\n";

        // Показываем ОРИГИНАЛЬНЫЙ тип данных
        echo "🔍 Type: {$dataType}\n";
        echo str_repeat('─', 80) . "\n";
        
        if (is_string($content)) {
            echo $content . "\n";
        } else {
            echo print_r($content, true) . "\n";
        }

        echo "\n";
        
        // Принудительный flush для немедленного вывода
        flush();
    }
}
