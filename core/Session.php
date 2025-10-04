<?php declare(strict_types=1);

namespace Core;

use Core\Facades\Facade;
use Core\Contracts\SessionInterface;

/**
 * Session Facade
 * 
 * Статический фасад для SessionManager
 * Все методы делегируются к SessionInterface через DI контейнер
 * 
 * @method static bool start(array $options = [])
 * @method static bool isStarted()
 * @method static string id()
 * @method static void setId(string $id)
 * @method static string name()
 * @method static void setName(string $name)
 * @method static bool regenerate(bool $deleteOldSession = true)
 * @method static bool destroy()
 * @method static void save()
 * @method static mixed get(string $key, mixed $default = null)
 * @method static void set(string $key, mixed $value)
 * @method static bool has(string $key)
 * @method static void delete(string $key)
 * @method static array all()
 * @method static void clear()
 * @method static mixed pull(string $key, mixed $default = null)
 * @method static void push(string $key, mixed $value)
 * @method static int increment(string $key, int $amount = 1)
 * @method static int decrement(string $key, int $amount = 1)
 * @method static mixed remember(string $key, callable $callback)
 * @method static void flash(string $key, mixed $value)
 * @method static mixed getFlash(string $key, mixed $default = null)
 * @method static bool hasFlash(string $key)
 * @method static array getAllFlash()
 * @method static string generateCsrfToken()
 * @method static string|null getCsrfToken()
 * @method static bool verifyCsrfToken(string $token)
 * @method static void setPreviousUrl(string $url)
 * @method static string getPreviousUrl(string $default = '/')
 * @method static array getCookieParams()
 * @method static void setCookieParams(int $lifetime, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = true, string $samesite = 'Lax')
 * 
 * @see \Core\Services\SessionManager
 */
class Session extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SessionInterface::class;
    }
}
