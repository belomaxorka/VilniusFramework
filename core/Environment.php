<?php declare(strict_types=1);

namespace Core;

use InvalidArgumentException;

class Environment
{
    public const string DEVELOPMENT = 'development';
    public const string PRODUCTION = 'production';
    public const string TESTING = 'testing';

    private static ?string $environment = null;

    /**
     * Получить текущее окружение
     */
    public static function get(): string
    {
        if (self::$environment === null) {
            self::$environment = self::detect();
        }

        return self::$environment;
    }

    /**
     * Установить окружение
     */
    public static function set(string $environment): void
    {
        if (!in_array($environment, [self::DEVELOPMENT, self::PRODUCTION, self::TESTING])) {
            throw new InvalidArgumentException("Invalid environment: $environment");
        }

        self::$environment = $environment;
        Env::set('APP_ENV', $environment);
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
        // Получаем значение APP_DEBUG (null если не установлено)
        $debug = Env::get('APP_DEBUG', null);
        
        // В development режиме debug включен по умолчанию (если явно не выключен)
        if (self::isDevelopment()) {
            // Если APP_DEBUG не установлен - включаем по умолчанию
            if ($debug === null) {
                return true;
            }
            // Проверяем явное значение
            return $debug === true || $debug === 'true' || $debug === '1' || $debug === 1;
        }

        // В других режимах (production, testing) debug выключен по умолчанию (включаем только если явно true)
        return $debug === true || $debug === 'true' || $debug === '1' || $debug === 1;
    }

    /**
     * Определить окружение из переменных окружения
     */
    private static function detect(): string
    {
        // Проверяем переменную окружения
        $env = Env::get('APP_ENV');
        if ($env && in_array($env, [self::DEVELOPMENT, self::PRODUCTION, self::TESTING])) {
            return $env;
        }

        // Если APP_ENV не установлен, по умолчанию продакшен
        return self::PRODUCTION;
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
        Env::clearCache();
    }
}
