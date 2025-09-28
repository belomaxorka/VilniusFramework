<?php declare(strict_types=1);

namespace Core;

use RuntimeException;

class Env
{
    /**
     * Кешированные значения переменных окружения
     */
    private static array $cache = [];

    /**
     * Флаг для отслеживания загрузки .env файла
     */
    private static bool $loaded = false;

    /**
     * Получить значение переменной окружения
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // Автоматически загружаем .env файл при первом обращении
        if (!self::$loaded) {
            self::load();
        }

        // Проверяем кеш
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        // Получаем из $_ENV или $_SERVER
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;

        if ($value === null) {
            self::$cache[$key] = $default;
            return $default;
        }

        // Парсим и кешируем значение
        $parsed = self::parseValue($value);
        self::$cache[$key] = $parsed;

        return $parsed;
    }

    /**
     * Установить переменную окружения
     */
    public static function set(string $key, mixed $value): void
    {
        $stringValue = (string)$value;

        // Устанавливаем в системные переменные
        $_ENV[$key] = $stringValue;
        $_SERVER[$key] = $stringValue;
        putenv("$key=$stringValue");

        // Обновляем кеш
        self::$cache[$key] = $value;
    }

    /**
     * Проверить существование переменной
     */
    public static function has(string $key): bool
    {
        if (!self::$loaded) {
            self::load();
        }

        return isset($_ENV[$key]) || isset($_SERVER[$key]);
    }

    /**
     * Получить все переменные окружения
     */
    public static function all(): array
    {
        if (!self::$loaded) {
            self::load();
        }

        return array_merge($_SERVER, $_ENV);
    }

    /**
     * Загрузить .env файл
     */
    public static function load(string $path = null, bool $required = false): bool
    {
        if (self::$loaded && $path === null) {
            return true;
        }

        if (!$path || !is_file($path)) {
            if ($required) {
                throw new RuntimeException("Required environment file not found: " . ($path ?? 'any .env file'));
            }

            self::$loaded = true;
            return false;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);

            // Пропускаем комментарии
            if (str_starts_with($line, '#')) {
                continue;
            }

            // Парсим строку вида KEY=VALUE
            if (str_contains($line, '=')) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Удаляем кавычки если есть
                $value = self::removeQuotes($value);

                // Устанавливаем только если переменная еще не установлена
                if (!isset($_ENV[$key]) && !isset($_SERVER[$key])) {
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }

        self::$loaded = true;
        return true;
    }

    /**
     * Очистить кеш
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * Удалить кавычки из значения
     */
    private static function removeQuotes(string $value): string
    {
        if ((str_starts_with($value, '"') && strrpos($value, '"') === strlen($value) - 1) ||
            (str_starts_with($value, "'") && strrpos($value, "'") === strlen($value) - 1)) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    /**
     * Парсить значение переменной окружения
     */
    private static function parseValue(string $value): mixed
    {
        $value = trim($value);

        // Boolean значения
        $lower = strtolower($value);
        if (in_array($lower, ['true', '1', 'yes', 'on'])) {
            return true;
        }
        if (in_array($lower, ['false', '0', 'no', 'off', ''])) {
            return false;
        }

        // Null
        if (in_array($lower, ['null', 'nil'])) {
            return null;
        }

        // Числа
        if (is_numeric($value)) {
            if (str_contains($value, '.')) {
                return (float)$value;
            }
            return (int)$value;
        }

        // JSON
        if ((str_starts_with($value, '{') && strrpos($value, '}') === strlen($value) - 1) ||
            (str_starts_with($value, '[') && strrpos($value, ']') === strlen($value) - 1)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }
}
