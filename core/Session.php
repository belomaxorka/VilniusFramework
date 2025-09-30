<?php declare(strict_types=1);

namespace Core;

/**
 * Класс для работы с PHP сессиями
 */
class Session
{
    private static bool $started = false;

    /**
     * Запустить сессию
     */
    public static function start(array $options = []): bool
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        // Настройки безопасности по умолчанию
        $defaultOptions = [
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => true,
        ];

        // Если HTTPS, устанавливаем secure cookie
        if (Http::isSecure()) {
            $defaultOptions['cookie_secure'] = true;
        }

        $options = array_merge($defaultOptions, $options);

        self::$started = session_start($options);
        
        return self::$started;
    }

    /**
     * Проверить, запущена ли сессия
     */
    public static function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Получить значение из сессии
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::ensureStarted();
        
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Установить значение в сессию
     */
    public static function set(string $key, mixed $value): void
    {
        self::ensureStarted();
        
        $_SESSION[$key] = $value;
    }

    /**
     * Проверить существование ключа в сессии
     */
    public static function has(string $key): bool
    {
        self::ensureStarted();
        
        return isset($_SESSION[$key]);
    }

    /**
     * Удалить значение из сессии
     */
    public static function delete(string $key): void
    {
        self::ensureStarted();
        
        unset($_SESSION[$key]);
    }

    /**
     * Получить все данные сессии
     */
    public static function all(): array
    {
        self::ensureStarted();
        
        return $_SESSION;
    }

    /**
     * Очистить все данные сессии
     */
    public static function clear(): void
    {
        self::ensureStarted();
        
        $_SESSION = [];
    }

    /**
     * Уничтожить сессию полностью
     */
    public static function destroy(): bool
    {
        self::ensureStarted();
        
        $_SESSION = [];
        
        // Удаляем cookie сессии
        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 3600,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        self::$started = false;
        
        return session_destroy();
    }

    /**
     * Регенерировать ID сессии (для безопасности)
     */
    public static function regenerate(bool $deleteOldSession = true): bool
    {
        self::ensureStarted();
        
        return session_regenerate_id($deleteOldSession);
    }

    /**
     * Получить ID сессии
     */
    public static function id(): string
    {
        return session_id();
    }

    /**
     * Установить ID сессии
     */
    public static function setId(string $id): void
    {
        session_id($id);
    }

    /**
     * Получить имя сессии
     */
    public static function name(): string
    {
        return session_name();
    }

    /**
     * Установить имя сессии
     */
    public static function setName(string $name): void
    {
        session_name($name);
    }

    /**
     * Flash сообщение (доступно только в следующем запросе)
     */
    public static function flash(string $key, mixed $value): void
    {
        self::set("_flash.$key", $value);
    }

    /**
     * Получить flash сообщение
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = self::get("_flash.$key", $default);
        self::delete("_flash.$key");
        
        return $value;
    }

    /**
     * Проверить существование flash сообщения
     */
    public static function hasFlash(string $key): bool
    {
        return self::has("_flash.$key");
    }

    /**
     * Получить все flash сообщения и очистить их
     */
    public static function getAllFlash(): array
    {
        $flash = [];
        
        foreach (self::all() as $key => $value) {
            if (str_starts_with($key, '_flash.')) {
                $flashKey = substr($key, 7);
                $flash[$flashKey] = $value;
                self::delete($key);
            }
        }
        
        return $flash;
    }

    /**
     * Сохранить предыдущий URL (для redirect back)
     */
    public static function setPreviousUrl(string $url): void
    {
        self::set('_previous_url', $url);
    }

    /**
     * Получить предыдущий URL
     */
    public static function getPreviousUrl(string $default = '/'): string
    {
        return self::get('_previous_url', $default);
    }

    /**
     * Получить и удалить значение (pull pattern)
     */
    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = self::get($key, $default);
        self::delete($key);
        
        return $value;
    }

    /**
     * Добавить значение в массив в сессии
     */
    public static function push(string $key, mixed $value): void
    {
        $array = self::get($key, []);
        
        if (!is_array($array)) {
            $array = [$array];
        }
        
        $array[] = $value;
        
        self::set($key, $array);
    }

    /**
     * Увеличить числовое значение
     */
    public static function increment(string $key, int $amount = 1): int
    {
        $value = (int)self::get($key, 0);
        $value += $amount;
        self::set($key, $value);
        
        return $value;
    }

    /**
     * Уменьшить числовое значение
     */
    public static function decrement(string $key, int $amount = 1): int
    {
        return self::increment($key, -$amount);
    }

    /**
     * Генерировать CSRF токен
     */
    public static function generateCsrfToken(): string
    {
        if (!self::has('_csrf_token')) {
            $token = bin2hex(random_bytes(32));
            self::set('_csrf_token', $token);
        }
        
        return self::get('_csrf_token');
    }

    /**
     * Получить CSRF токен
     */
    public static function getCsrfToken(): ?string
    {
        return self::get('_csrf_token');
    }

    /**
     * Проверить CSRF токен
     */
    public static function verifyCsrfToken(string $token): bool
    {
        $sessionToken = self::getCsrfToken();
        
        if (!$sessionToken) {
            return false;
        }
        
        return hash_equals($sessionToken, $token);
    }

    /**
     * Убедиться, что сессия запущена
     */
    private static function ensureStarted(): void
    {
        if (!self::isStarted()) {
            self::start();
        }
    }

    /**
     * Получить параметры cookie сессии
     */
    public static function getCookieParams(): array
    {
        return session_get_cookie_params();
    }

    /**
     * Установить параметры cookie сессии
     */
    public static function setCookieParams(
        int $lifetime,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true,
        string $samesite = 'Lax'
    ): void {
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite,
        ]);
    }

    /**
     * Сохранить сессию и закрыть запись
     */
    public static function save(): void
    {
        if (self::isStarted()) {
            session_write_close();
            self::$started = false;
        }
    }

    /**
     * "Запомнить" значение - если не существует, установить
     */
    public static function remember(string $key, callable $callback): mixed
    {
        if (self::has($key)) {
            return self::get($key);
        }
        
        $value = $callback();
        self::set($key, $value);
        
        return $value;
    }
}

