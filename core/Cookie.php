<?php declare(strict_types=1);

namespace Core;

/**
 * Класс для работы с HTTP Cookie
 */
class Cookie
{
    /**
     * Установить cookie
     *
     * @param string $name Имя cookie
     * @param string $value Значение cookie
     * @param int $expires Время жизни в секундах (0 = до закрытия браузера)
     * @param string $path Путь на сервере
     * @param string $domain Домен
     * @param bool $secure Только для HTTPS
     * @param bool $httponly Недоступна для JavaScript
     * @param string $samesite Политика SameSite: 'Lax', 'Strict', 'None'
     */
    public static function set(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true,
        string $samesite = 'Lax'
    ): bool {
        // Если expires > 0, добавляем к текущему времени
        $expiresTime = $expires > 0 ? time() + $expires : 0;

        $options = [
            'expires' => $expiresTime,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite,
        ];

        return setcookie($name, $value, $options);
    }

    /**
     * Получить значение cookie
     */
    public static function get(string $name, mixed $default = null): mixed
    {
        return $_COOKIE[$name] ?? $default;
    }

    /**
     * Проверить существование cookie
     */
    public static function has(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Удалить cookie
     */
    public static function delete(string $name, string $path = '/', string $domain = ''): bool
    {
        if (!self::has($name)) {
            return false;
        }

        unset($_COOKIE[$name]);

        return setcookie($name, '', [
            'expires' => time() - 3600,
            'path' => $path,
            'domain' => $domain,
        ]);
    }

    /**
     * Получить все cookies
     */
    public static function all(): array
    {
        return $_COOKIE;
    }

    /**
     * Удалить все cookies
     */
    public static function clear(string $path = '/', string $domain = ''): void
    {
        foreach (array_keys($_COOKIE) as $name) {
            self::delete($name, $path, $domain);
        }
    }

    /**
     * Установить cookie с автоматическим определением secure
     * (secure = true если HTTPS)
     */
    public static function setSecure(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $httponly = true,
        string $samesite = 'Lax'
    ): bool {
        return self::set(
            $name,
            $value,
            $expires,
            $path,
            $domain,
            Http::isSecure(),
            $httponly,
            $samesite
        );
    }

    /**
     * Установить cookie на определенное количество дней
     */
    public static function setForDays(
        string $name,
        string $value,
        int $days = 30,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true,
        string $samesite = 'Lax'
    ): bool {
        $expires = 60 * 60 * 24 * $days;
        return self::set($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite);
    }

    /**
     * Установить cookie на определенное количество часов
     */
    public static function setForHours(
        string $name,
        string $value,
        int $hours = 1,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true,
        string $samesite = 'Lax'
    ): bool {
        $expires = 60 * 60 * $hours;
        return self::set($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite);
    }

    /**
     * Установить постоянную cookie (на 5 лет)
     */
    public static function forever(
        string $name,
        string $value,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true,
        string $samesite = 'Lax'
    ): bool {
        $expires = 60 * 60 * 24 * 365 * 5; // 5 лет
        return self::set($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite);
    }

    /**
     * Получить cookie как JSON
     */
    public static function getJson(string $name, mixed $default = null): mixed
    {
        $value = self::get($name);
        
        if ($value === null) {
            return $default;
        }

        $decoded = json_decode($value, true);
        
        return $decoded ?? $default;
    }

    /**
     * Установить cookie со значением в формате JSON
     */
    public static function setJson(
        string $name,
        mixed $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true,
        string $samesite = 'Lax'
    ): bool {
        $jsonValue = json_encode($value);
        
        if ($jsonValue === false) {
            return false;
        }

        return self::set($name, $jsonValue, $expires, $path, $domain, $secure, $httponly, $samesite);
    }
}

