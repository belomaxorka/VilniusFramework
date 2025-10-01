<?php declare(strict_types=1);

namespace Core\Middleware;

use Core\Session;
use Core\Http;

/**
 * CSRF Protection Middleware
 * 
 * Проверяет CSRF токен для POST, PUT, PATCH, DELETE запросов
 */
class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * Методы, которые требуют CSRF проверки
     */
    protected array $methods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * URI исключения (не требуют CSRF проверки)
     */
    protected array $except = [];

    public function __construct(array $except = [])
    {
        $this->except = $except;
    }

    /**
     * Обработать запрос через middleware
     */
    public function handle(callable $next): mixed
    {
        $method = Http::getMethod();
        $uri = trim(parse_url(Http::getUri(), PHP_URL_PATH) ?? '', '/');

        // Проверяем, нужна ли CSRF защита для этого запроса
        if ($this->shouldVerifyCsrf($method, $uri)) {
            $this->verifyCsrfToken();
        }

        // Генерируем токен для следующего запроса (если еще не существует)
        Session::generateCsrfToken();

        return $next();
    }

    /**
     * Определить, нужна ли CSRF проверка
     */
    protected function shouldVerifyCsrf(string $method, string $uri): bool
    {
        // Проверяем метод
        if (!in_array($method, $this->methods)) {
            return false;
        }

        // Проверяем исключения
        foreach ($this->except as $pattern) {
            $pattern = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$#';
            if (preg_match($pattern, $uri)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Проверить CSRF токен
     */
    protected function verifyCsrfToken(): void
    {
        $token = $this->getTokenFromRequest();

        if (!$token || !Session::verifyCsrfToken($token)) {
            $this->handleInvalidToken();
        }
    }

    /**
     * Получить токен из запроса
     */
    protected function getTokenFromRequest(): ?string
    {
        // Проверяем POST данные
        if (isset($_POST['_csrf_token'])) {
            return $_POST['_csrf_token'];
        }

        // Проверяем заголовки
        $headers = [
            'X-CSRF-TOKEN',
            'X-XSRF-TOKEN',
        ];

        foreach ($headers as $header) {
            $value = $_SERVER['HTTP_' . str_replace('-', '_', $header)] ?? null;
            if ($value) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Обработать невалидный токен
     */
    protected function handleInvalidToken(): void
    {
        http_response_code(419); // 419 Page Expired (Laravel convention)
        
        if ($this->isJsonRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'CSRF token mismatch',
                'message' => 'The CSRF token is invalid or expired. Please refresh the page and try again.',
            ]);
        } else {
            echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>419 - CSRF Token Mismatch</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #e74c3c; margin: 0 0 20px; }
        p { color: #555; line-height: 1.6; }
        a { color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>419 - CSRF Token Mismatch</h1>
        <p>The CSRF token is invalid or expired. This usually happens when:</p>
        <ul style="text-align: left; margin: 20px auto; display: inline-block;">
            <li>Your session has expired</li>
            <li>The page has been open for too long</li>
            <li>You opened the form in multiple tabs</li>
        </ul>
        <p>Please <a href="javascript:history.back()">go back</a> and refresh the page, then try again.</p>
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

