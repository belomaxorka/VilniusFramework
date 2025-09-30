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
        self::setRaw($key, $stringValue);

        // Обновляем кеш - сохраняем парсированное значение для консистентности
        self::$cache[$key] = self::parseValue($stringValue);
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
     *
     * @param string|null $path Путь к .env файлу (если null, ищет автоматически)
     * @param bool $required Выбросить исключение если файл не найден
     * @param bool $reload Перезагрузить даже если уже загружен (с переопределением переменных)
     * @return bool true если файл загружен успешно, false если не найден
     */
    public static function load(?string $path = null, bool $required = false, bool $reload = false): bool
    {
        // Если уже загружен и не требуется перезагрузка, возвращаем true
        if (self::$loaded && !$reload) {
            return true;
        }

        // Если путь не указан, пытаемся найти .env файл в корне проекта
        if ($path === null) {
            $path = self::findEnvFile();
        }

        // Если файл не найден
        if ($path === null || !is_file($path)) {
            if ($required) {
                throw new RuntimeException("Environment file not found" . ($path ? ": {$path}" : ''));
            }
            // Отмечаем как загруженный только если мы пытались автоматически найти файл
            // Это предотвращает повторные попытки поиска при каждом вызове get()
            self::$loaded = true;
            return false;
        }

        // Загружаем переменные из файла
        self::loadFile($path, $reload);
        self::$loaded = true;

        return true;
    }

    /**
     * Найти .env файл в стандартных местах
     */
    protected static function findEnvFile(): ?string
    {
        $possiblePaths = [
            getcwd() . '/.env',
            dirname(__DIR__) . '/.env',
            __DIR__ . '/../.env',
            __DIR__ . '/.env'
        ];

        foreach ($possiblePaths as $possiblePath) {
            if (is_file($possiblePath)) {
                return $possiblePath;
            }
        }

        return null;
    }

    /**
     * Загрузить переменные из файла
     *
     * @param string $path Путь к файлу
     * @param bool $override Переопределять существующие переменные
     */
    protected static function loadFile(string $path, bool $override = false): void
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Пропускаем комментарии и пустые строки
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Парсим строку вида KEY=VALUE
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Удаляем кавычки если есть
                $value = self::removeQuotes($value);

                // Устанавливаем переменную
                // При reload (override=true) переопределяем существующие
                // При обычной загрузке - только если еще не установлена
                if ($override || (!isset($_ENV[$key]) && !isset($_SERVER[$key]))) {
                    self::setRaw($key, $value);
                }
            }
        }
    }

    /**
     * Установить переменную без парсинга значения (для внутреннего использования)
     */
    protected static function setRaw(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("$key=$value");
    }

    /**
     * Очистить кеш
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * Сбросить состояние класса (полезно для тестов)
     */
    public static function reset(): void
    {
        self::$cache = [];
        self::$loaded = false;
    }

    /**
     * Удалить кавычки из значения
     */
    private static function removeQuotes(string $value): string
    {
        $length = strlen($value);

        // Проверяем двойные кавычки
        if ($length >= 2 && str_starts_with($value, '"') && str_ends_with($value, '"')) {
            return substr($value, 1, -1);
        }

        // Проверяем одинарные кавычки
        if ($length >= 2 && str_starts_with($value, "'") && str_ends_with($value, "'")) {
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
