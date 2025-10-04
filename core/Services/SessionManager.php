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

    public function setId(string $id): void
    {
        if ($this->isStarted()) {
            throw new \RuntimeException('Cannot set session ID after session has started');
        }
        session_id($id);
    }

    public function name(): string
    {
        return session_name();
    }

    public function setName(string $name): void
    {
        if ($this->isStarted()) {
            throw new \RuntimeException('Cannot set session name after session has started');
        }
        session_name($name);
    }

    public function save(): void
    {
        if ($this->isStarted()) {
            session_write_close();
            $this->started = false;
        }
    }

    // ========== Работа с данными ==========

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->delete($key);
        return $value;
    }

    public function push(string $key, mixed $value): void
    {
        $array = $this->get($key, []);
        
        if (!is_array($array)) {
            $array = [$array];
        }
        
        $array[] = $value;
        $this->set($key, $array);
    }

    public function increment(string $key, int $amount = 1): int
    {
        $value = (int)$this->get($key, 0);
        $value += $amount;
        $this->set($key, $value);
        
        return $value;
    }

    public function decrement(string $key, int $amount = 1): int
    {
        return $this->increment($key, -$amount);
    }

    public function remember(string $key, callable $callback): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        
        $value = $callback();
        $this->set($key, $value);
        
        return $value;
    }

    // ========== Flash сообщения ==========

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

    public function getAllFlash(): array
    {
        $flash = [];
        
        foreach ($this->all() as $key => $value) {
            if (str_starts_with($key, '_flash.')) {
                $flashKey = substr($key, 7);
                $flash[$flashKey] = $value;
                $this->delete($key);
            }
        }
        
        return $flash;
    }

    // ========== Previous URL ==========

    public function setPreviousUrl(string $url): void
    {
        $this->set('_previous_url', $url);
    }

    public function getPreviousUrl(string $default = '/'): string
    {
        return $this->get('_previous_url', $default);
    }

    // ========== Cookie параметры ==========

    public function getCookieParams(): array
    {
        return session_get_cookie_params();
    }

    public function setCookieParams(
        int $lifetime,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true,
        string $samesite = 'Lax'
    ): void {
        if ($this->isStarted()) {
            throw new \RuntimeException('Cannot set cookie params after session has started');
        }

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite,
        ]);
    }

    // ========== Private helpers ==========

    private function ensureStarted(): void
    {
        if (!$this->isStarted()) {
            $this->start();
        }
    }
}

