<?php declare(strict_types=1);

namespace Core\Middleware;

use Core\Session;

/**
 * Authentication Middleware
 * 
 * Проверяет, авторизован ли пользователь
 */
class AuthMiddleware implements MiddlewareInterface
{
    protected string $redirectTo;
    protected string $sessionKey;

    public function __construct(string $redirectTo = '/login', string $sessionKey = 'user_id')
    {
        $this->redirectTo = $redirectTo;
        $this->sessionKey = $sessionKey;
    }

    /**
     * Обработать запрос через middleware
     */
    public function handle(callable $next): mixed
    {
        if (!$this->isAuthenticated()) {
            $this->handleUnauthenticated();
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

    /**
     * Обработать неавторизованного пользователя
     */
    protected function handleUnauthenticated(): void
    {
        if ($this->isJsonRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Unauthorized',
                'message' => 'Authentication required.',
            ]);
        } else {
            // Сохраняем URL, на который пользователь пытался попасть
            Session::set('redirect_after_login', \Core\Http::getUri());
            
            header('Location: ' . $this->redirectTo);
        }
        
        exit;
    }

    /**
     * Проверить, является ли запрос JSON
     */
    protected function isJsonRequest(): bool
    {
        return \Core\Http::isJson() || \Core\Http::acceptsJson();
    }
}

