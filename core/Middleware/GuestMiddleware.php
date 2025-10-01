<?php declare(strict_types=1);

namespace Core\Middleware;

use Core\Session;

/**
 * Guest Middleware
 * 
 * Проверяет, что пользователь НЕ авторизован (для страниц login/register)
 */
class GuestMiddleware implements MiddlewareInterface
{
    protected string $redirectTo;
    protected string $sessionKey;

    public function __construct(string $redirectTo = '/', string $sessionKey = 'user_id')
    {
        $this->redirectTo = $redirectTo;
        $this->sessionKey = $sessionKey;
    }

    /**
     * Обработать запрос через middleware
     */
    public function handle(callable $next): mixed
    {
        if ($this->isAuthenticated()) {
            header('Location: ' . $this->redirectTo);
            exit;
        }

        return $next();
    }

    /**
     * Проверить, авторизован ли пользователь
     */
    protected function isAuthenticated(): bool
    {
        return Session::has($this->sessionKey);
    }
}

