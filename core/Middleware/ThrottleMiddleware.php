<?php declare(strict_types=1);

namespace Core\Middleware;

use Core\Session;

/**
 * Rate Limiting / Throttle Middleware
 * 
 * Ограничивает количество запросов от одного IP
 */
class ThrottleMiddleware implements MiddlewareInterface
{
    protected int $maxAttempts;
    protected int $decayMinutes;

    public function __construct(int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    /**
     * Обработать запрос через middleware
     */
    public function handle(callable $next): mixed
    {
        $key = $this->resolveRequestKey();

        if ($this->tooManyAttempts($key)) {
            $this->handleTooManyAttempts($key);
        }

        $this->incrementAttempts($key);

        return $next();
    }

    /**
     * Получить ключ для текущего запроса
     */
    protected function resolveRequestKey(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        return 'throttle:' . sha1($ip . '|' . $uri);
    }

    /**
     * Проверить, превышен ли лимит
     */
    protected function tooManyAttempts(string $key): bool
    {
        $attempts = Session::get($key . ':attempts', 0);
        
        return $attempts >= $this->maxAttempts;
    }

    /**
     * Увеличить счетчик попыток
     */
    protected function incrementAttempts(string $key): void
    {
        $attempts = Session::get($key . ':attempts', 0);
        $expiresAt = Session::get($key . ':expires_at');

        // Если время истекло, сбрасываем счетчик
        if ($expiresAt && time() > $expiresAt) {
            $attempts = 0;
        }

        $attempts++;
        
        Session::set($key . ':attempts', $attempts);
        Session::set($key . ':expires_at', time() + ($this->decayMinutes * 60));
    }

    /**
     * Обработать превышение лимита
     */
    protected function handleTooManyAttempts(string $key): void
    {
        $expiresAt = Session::get($key . ':expires_at', time());
        $retryAfter = max(0, $expiresAt - time());

        http_response_code(429); // Too Many Requests
        header('Retry-After: ' . $retryAfter);
        
        if ($this->isJsonRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $retryAfter,
            ]);
        } else {
            echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>429 - Too Many Requests</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #f39c12; margin: 0 0 20px; }
        p { color: #555; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <h1>429 - Too Many Requests</h1>
        <p>You have made too many requests. Please wait <strong>' . $retryAfter . ' seconds</strong> before trying again.</p>
    </div>
</body>
</html>';
        }
        
        exit;
    }

    /**
     * Проверить, является ли запрос JSON
     */
    protected function isJsonRequest(): bool
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        
        return str_contains($contentType, 'application/json') 
            || str_contains($accept, 'application/json');
    }
}

