<?php declare(strict_types=1);

namespace Core\Middleware;

use Core\Contracts\HttpInterface;
use Core\Contracts\SessionInterface;

/**
 * Authentication Middleware
 * 
 * Проверяет, авторизован ли пользователь
 */
class AuthMiddleware implements MiddlewareInterface
{
    protected string $redirectTo;
    protected string $sessionKey;

    /**
     * Constructor with Dependency Injection
     */
    public function __construct(
        protected SessionInterface $session,
        protected HttpInterface $http,
        string $redirectTo = '/login',
        string $sessionKey = 'user_id'
    ) {
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
        return $this->session->has($this->sessionKey);
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
            $this->session->set('redirect_after_login', $this->http->getUri());
            
            header('Location: ' . $this->redirectTo);
        }
        
        exit;
    }

    /**
     * Проверить, является ли запрос JSON
     */
    protected function isJsonRequest(): bool
    {
        return $this->http->isJson() || $this->http->acceptsJson();
    }
}

