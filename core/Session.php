<?php declare(strict_types=1);

namespace Core;

use Core\Facades\Facade;
use Core\Contracts\SessionInterface;

/**
 * Session Facade
 * 
 * Статический фасад для SessionManager
 * Обеспечивает обратную совместимость со старым API
 * 
 * @method static bool start(array $options = [])
 * @method static bool isStarted()
 * @method static mixed get(string $key, mixed $default = null)
 * @method static void set(string $key, mixed $value)
 * @method static bool has(string $key)
 * @method static void delete(string $key)
 * @method static array all()
 * @method static void clear()
 * @method static bool destroy()
 * @method static bool regenerate(bool $deleteOldSession = true)
 * @method static string id()
 * @method static void flash(string $key, mixed $value)
 * @method static mixed getFlash(string $key, mixed $default = null)
 * @method static bool hasFlash(string $key)
 * @method static string generateCsrfToken()
 * @method static string|null getCsrfToken()
 * @method static bool verifyCsrfToken(string $token)
 * 
 * @see \Core\Services\SessionManager
 */
class Session extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SessionInterface::class;
    }

    // Дополнительные утилитные методы из старого API
    public static function setId(string $id): void
    {
        session_id($id);
    }

    public static function name(): string
    {
        return session_name();
    }

    public static function setName(string $name): void
    {
        session_name($name);
    }

    public static function setPreviousUrl(string $url): void
    {
        static::set('_previous_url', $url);
    }

    public static function getPreviousUrl(string $default = '/'): string
    {
        return static::get('_previous_url', $default);
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = static::get($key, $default);
        static::delete($key);
        return $value;
    }

    public static function push(string $key, mixed $value): void
    {
        $array = static::get($key, []);
        
        if (!is_array($array)) {
            $array = [$array];
        }
        
        $array[] = $value;
        
        static::set($key, $array);
    }

    public static function increment(string $key, int $amount = 1): int
    {
        $value = (int)static::get($key, 0);
        $value += $amount;
        static::set($key, $value);
        
        return $value;
    }

    public static function decrement(string $key, int $amount = 1): int
    {
        return static::increment($key, -$amount);
    }

    public static function getAllFlash(): array
    {
        $flash = [];
        
        foreach (static::all() as $key => $value) {
            if (str_starts_with($key, '_flash.')) {
                $flashKey = substr($key, 7);
                $flash[$flashKey] = $value;
                static::delete($key);
            }
        }
        
        return $flash;
    }

    public static function getCookieParams(): array
    {
        return session_get_cookie_params();
    }

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

    public static function save(): void
    {
        if (static::isStarted()) {
            session_write_close();
        }
    }

    public static function remember(string $key, callable $callback): mixed
    {
        if (static::has($key)) {
            return static::get($key);
        }
        
        $value = $callback();
        static::set($key, $value);
        
        return $value;
    }
}
