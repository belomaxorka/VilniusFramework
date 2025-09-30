<?php declare(strict_types=1);

namespace Core;

use InvalidArgumentException;

class Environment
{
    public const string DEVELOPMENT = 'development';
    public const string PRODUCTION = 'production';
    public const string TESTING = 'testing';

    private static ?string $environment = null;
    private static ?bool $isDebugCache = null;

    /**
     * Получить текущее окружение
     */
    public static function get(): string
    {
        // Простое кеширование без лишнего метода detect()
        return self::$environment ??= Env::get('APP_ENV', self::PRODUCTION);
    }

    /**
     * Установить окружение (для тестов)
     */
    public static function set(string $environment): void
    {
        if (!in_array($environment, [self::DEVELOPMENT, self::PRODUCTION, self::TESTING])) {
            throw new InvalidArgumentException("Invalid environment: $environment");
        }

        self::$environment = $environment;
        self::$isDebugCache = null; // Сбрасываем кеш при изменении окружения

        // Удаляем APP_DEBUG из окружения чтобы не было конфликтов между тестами
        // Это позволит isDebug() использовать значения по умолчанию для каждого окружения
        unset($_ENV['APP_DEBUG'], $_SERVER['APP_DEBUG']);
        putenv('APP_DEBUG');

        // Очищаем кеш Env ПЕРЕД установкой нового окружения
        Env::clearCache();

        // Теперь устанавливаем новое окружение
        Env::set('APP_ENV', $environment);
    }

    /**
     * Установить режим отладки (для тестов)
     * Автоматически сбрасывает кеш
     */
    public static function setDebug(bool $debug): void
    {
        Env::set('APP_DEBUG', $debug);
        self::$isDebugCache = null; // Сбрасываем кеш
    }

    /**
     * Проверить, является ли окружение разработкой
     */
    public static function isDevelopment(): bool
    {
        return self::get() === self::DEVELOPMENT;
    }

    /**
     * Проверить, является ли окружение продакшеном
     */
    public static function isProduction(): bool
    {
        return self::get() === self::PRODUCTION;
    }

    /**
     * Проверить, является ли окружение тестовым
     */
    public static function isTesting(): bool
    {
        return self::get() === self::TESTING;
    }

    /**
     * Проверить, включен ли режим отладки
     */
    public static function isDebug(): bool
    {
        // Важно: НЕ кешируем в тестовом окружении, так как тесты часто меняют настройки
        if (self::isTesting() && self::$isDebugCache !== null) {
            self::$isDebugCache = null; // Сбрасываем кеш в тестах
        }

        // Кешируем результат для производительности (вызывается очень часто)
        if (self::$isDebugCache !== null) {
            return self::$isDebugCache;
        }

        $debug = Env::get('APP_DEBUG', null);

        // В development режиме debug включен по умолчанию (если явно не выключен)
        if (self::isDevelopment()) {
            self::$isDebugCache = $debug === null ? true : self::parseBool($debug);
        } // В других режимах (production, testing) debug выключен по умолчанию
        else {
            self::$isDebugCache = self::parseBool($debug);
        }

        return self::$isDebugCache;
    }

    /**
     * Парсить boolean значение из APP_DEBUG
     */
    private static function parseBool(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        // Проверяем строки и числа
        return $value === true || $value === 'true' || $value === '1' || $value === 1;
    }

    /**
     * Получить конфигурацию для текущего окружения
     */
    public static function getConfig(): array
    {
        return [
            'debug' => self::isDebug(),
            'error_reporting' => self::isDevelopment() ? E_ALL : 0,
            'display_errors' => self::isDevelopment() ? 1 : 0,
            'log_errors' => 1,
            'log_level' => self::isDevelopment() ? 'debug' : 'error',
        ];
    }

    /**
     * Очистить кеш окружения
     */
    public static function clearCache(): void
    {
        self::$environment = null;
        self::$isDebugCache = null;
        Env::clearCache();
    }
}
