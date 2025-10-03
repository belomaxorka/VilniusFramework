<?php declare(strict_types=1);

namespace Core;

class DumpClient
{
    private static string $host = '127.0.0.1';
    private static int $port = 9912;
    private static int $timeout = 1;
    private static bool $enabled = true;

    /**
     * Настроить клиент
     */
    public static function configure(string $host = '127.0.0.1', int $port = 9912, int $timeout = 1): void
    {
        self::$host = $host;
        self::$port = $port;
        self::$timeout = $timeout;
    }

    /**
     * Включить/выключить отправку
     */
    public static function enable(bool $enabled = true): void
    {
        self::$enabled = $enabled;
    }

    /**
     * Отправить данные на сервер
     */
    public static function send(mixed $data, ?string $label = null, ?string $type = 'dump'): bool
    {
        if (!self::$enabled || !Environment::isDebug()) {
            return false;
        }

        // Получаем полный backtrace для определения реального caller
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        
        // Ищем первый вызов НЕ из helpers и НЕ из DumpClient
        $caller = null;
        foreach ($backtrace as $trace) {
            $file = $trace['file'] ?? '';
            
            // Нормализуем путь для сравнения (Windows/Unix)
            $normalizedFile = str_replace('\\', '/', $file);
            
            // Пропускаем DumpClient и все helper файлы
            if (str_contains($normalizedFile, 'DumpClient.php') || 
                str_contains($normalizedFile, 'helpers/debug/') ||
                str_contains($normalizedFile, 'helpers\\debug\\')) {
                continue;
            }
            
            $caller = $trace;
            break;
        }
        
        // Fallback на первый элемент если ничего не найдено
        if (!$caller) {
            $caller = $backtrace[0] ?? [];
        }

        $payload = [
            'type' => $type,
            'label' => $label,
            'data_type' => gettype($data), // Сохраняем оригинальный тип
            'content' => self::formatData($data),
            'raw_data' => is_scalar($data) ? $data : null, // Сохраняем скалярные значения
            'file' => $caller['file'] ?? 'unknown',
            'line' => $caller['line'] ?? 0,
            'timestamp' => microtime(true),
        ];

        return self::sendToServer($payload);
    }

    /**
     * Dump на сервер
     */
    public static function dump(mixed $data, ?string $label = null): bool
    {
        return self::send($data, $label, 'dump');
    }

    /**
     * Проверить доступность сервера
     */
    public static function isServerAvailable(): bool
    {
        $connection = @stream_socket_client(
            "tcp://" . self::$host . ":" . self::$port,
            $errno,
            $errstr,
            self::$timeout
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
            'timeout' => self::$timeout,
            'enabled' => self::$enabled,
        ];
    }

    /**
     * Отправить на сервер
     */
    private static function sendToServer(array $payload): bool
    {
        $connection = @stream_socket_client(
            "tcp://" . self::$host . ":" . self::$port,
            $errno,
            $errstr,
            self::$timeout
        );

        if (!$connection) {
            return false;
        }

        $json = json_encode($payload);
        fwrite($connection, $json);
        fclose($connection);

        return true;
    }

    /**
     * Форматировать данные для вывода
     */
    private static function formatData(mixed $data): string
    {
        if (is_string($data)) {
            return $data;
        }

        if (is_scalar($data)) {
            return var_export($data, true);
        }

        // Для объектов и массивов используем Debug::varToString если доступен
        if (class_exists('\Core\Debug')) {
            return \Core\Debug::varToString($data);
        }

        return print_r($data, true);
    }
}
