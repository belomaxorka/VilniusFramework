<?php declare(strict_types=1);

namespace Core\Services;

use Core\Contracts\SessionInterface;
use Core\Contracts\HttpInterface;

/**
 * Session Manager
 * 
 * Instance-based реализация для работы с сессиями с внедрением зависимостей
 */
class SessionManager implements SessionInterface
{
    private bool $started = false;

    public function __construct(
        private HttpInterface $http
    ) {}

    public function start(array $options = []): bool
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        // Настройки безопасности по умолчанию
        $defaultOptions = [
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => true,
        ];

        // Используем внедренную зависимость вместо статического вызова
        if ($this->http->isSecure()) {
            $defaultOptions['cookie_secure'] = true;
        }

        $options = array_merge($defaultOptions, $options);

        $this->started = session_start($options);
        
        return $this->started;
    }

    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->ensureStarted();
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        $this->ensureStarted();
        return array_key_exists($key, $_SESSION);
    }

    public function delete(string $key): void
    {
        $this->ensureStarted();
        unset($_SESSION[$key]);
    }

    public function all(): array
    {
        $this->ensureStarted();
        return $_SESSION;
    }

    public function clear(): void
    {
        $this->ensureStarted();
        $_SESSION = [];
    }

    public function destroy(): bool
    {
        $this->ensureStarted();
        
        $_SESSION = [];
        
        // Используем внедренную зависимость
        if ($this->http->getCookie(session_name()) !== null) {
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
        
        $this->started = false;
        
        return session_destroy();
    }

    public function regenerate(bool $deleteOldSession = true): bool
    {
        $this->ensureStarted();
        return session_regenerate_id($deleteOldSession);
    }

    public function id(): string
    {
        return session_id();
    }

    public function flash(string $key, mixed $value): void
    {
        $this->set("_flash.$key", $value);
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $this->get("_flash.$key", $default);
        $this->delete("_flash.$key");
        return $value;
    }

    public function hasFlash(string $key): bool
    {
        return $this->has("_flash.$key");
    }

    public function generateCsrfToken(): string
    {
        if (!$this->has('_csrf_token')) {
            $token = bin2hex(random_bytes(32));
            $this->set('_csrf_token', $token);
        }
        
        return $this->get('_csrf_token');
    }

    public function getCsrfToken(): ?string
    {
        return $this->get('_csrf_token');
    }

    public function verifyCsrfToken(string $token): bool
    {
        $sessionToken = $this->getCsrfToken();
        
        if (!$sessionToken) {
            return false;
        }
        
        return hash_equals($sessionToken, $token);
    }

    private function ensureStarted(): void
    {
        if (!$this->isStarted()) {
            $this->start();
        }
    }
}

