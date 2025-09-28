<?php declare(strict_types=1);

namespace Core;

class Environment
{
    public const DEVELOPMENT = 'development';
    public const PRODUCTION = 'production';
    public const TESTING = 'testing';

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
            throw new \InvalidArgumentException("Invalid environment: {$environment}");
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
        return Env::get('APP_DEBUG', false) && !self::isProduction();
    }

    /**
     * Автоматически определить окружение
     */
    private static function detect(): string
    {
        // Сначала проверяем переменную окружения
        $env = Env::get('APP_ENV');
        if ($env && in_array($env, [self::DEVELOPMENT, self::PRODUCTION, self::TESTING])) {
            return $env;
        }

        // Проверяем переменную SERVER_NAME для локальной разработки
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        if (in_array($serverName, ['localhost', '127.0.0.1', '::1']) || 
            str_contains($serverName, '.local') || 
            str_contains($serverName, '.dev')) {
            return self::DEVELOPMENT;
        }

        // Проверяем переменную HTTP_HOST
        $httpHost = $_SERVER['HTTP_HOST'] ?? '';
        if (in_array($httpHost, ['localhost', '127.0.0.1', '::1']) || 
            str_contains($httpHost, '.local') || 
            str_contains($httpHost, '.dev')) {
            return self::DEVELOPMENT;
        }

        // По умолчанию продакшен
        return self::PRODUCTION;
    }

    /**
     * Получить конфигурацию для текущего окружения
     */
    public static function getConfig(): array
    {
        $config = [
            'debug' => self::isDebug(),
            'error_reporting' => self::isDevelopment() ? E_ALL : 0,
            'display_errors' => self::isDevelopment() ? 1 : 0,
            'log_errors' => 1,
            'log_level' => self::isDevelopment() ? 'debug' : 'error',
        ];

        return $config;
    }
}
