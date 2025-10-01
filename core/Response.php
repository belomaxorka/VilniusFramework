<?php declare(strict_types=1);

namespace Core;

/**
 * HTTP Response
 * 
 * Управление HTTP ответами
 */
class Response
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected mixed $content = '';
    protected static ?Response $instance = null;

    /**
     * HTTP статус коды
     */
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_MOVED_PERMANENTLY = 301;
    public const HTTP_FOUND = 302;
    public const HTTP_SEE_OTHER = 303;
    public const HTTP_NOT_MODIFIED = 304;
    public const HTTP_TEMPORARY_REDIRECT = 307;
    public const HTTP_PERMANENT_REDIRECT = 308;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_NOT_ACCEPTABLE = 406;
    public const HTTP_CONFLICT = 409;
    public const HTTP_UNPROCESSABLE_ENTITY = 422;
    public const HTTP_TOO_MANY_REQUESTS = 429;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_SERVICE_UNAVAILABLE = 503;

    /**
     * Получить глобальный экземпляр
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Установить статус код
     */
    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Получить статус код
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Установить заголовок
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Установить несколько заголовков
     */
    public function withHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }
        return $this;
    }

    /**
     * Получить все заголовки
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Установить контент
     */
    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Получить контент
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * JSON ответ
     */
    public function json(mixed $data, int $status = 200, array $headers = [], int $options = 0): self
    {
        $this->status($status);
        $this->header('Content-Type', 'application/json');
        $this->withHeaders($headers);
        $this->content = json_encode($data, $options | JSON_UNESCAPED_UNICODE);
        return $this;
    }

    /**
     * HTML ответ
     */
    public function html(string $content, int $status = 200, array $headers = []): self
    {
        $this->status($status);
        $this->header('Content-Type', 'text/html; charset=UTF-8');
        $this->withHeaders($headers);
        $this->content = $content;
        return $this;
    }

    /**
     * Plain text ответ
     */
    public function text(string $content, int $status = 200, array $headers = []): self
    {
        $this->status($status);
        $this->header('Content-Type', 'text/plain; charset=UTF-8');
        $this->withHeaders($headers);
        $this->content = $content;
        return $this;
    }

    /**
     * XML ответ
     */
    public function xml(string $content, int $status = 200, array $headers = []): self
    {
        $this->status($status);
        $this->header('Content-Type', 'application/xml; charset=UTF-8');
        $this->withHeaders($headers);
        $this->content = $content;
        return $this;
    }

    /**
     * Редирект
     */
    public function redirect(string $url, int $status = 302, array $headers = []): self
    {
        $this->status($status);
        $this->header('Location', $url);
        $this->withHeaders($headers);
        return $this;
    }

    /**
     * Редирект назад
     */
    public function back(int $status = 302): self
    {
        $referer = Http::getReferer();
        return $this->redirect($referer ?: '/', $status);
    }

    /**
     * Редирект на именованный роут
     */
    public function route(string $name, array $params = [], int $status = 302): self
    {
        $url = route($name, $params);
        return $this->redirect($url, $status);
    }

    /**
     * Download файла
     */
    public function download(string $path, ?string $name = null, array $headers = []): self
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }

        $name = $name ?: basename($path);
        
        $this->header('Content-Type', mime_content_type($path) ?: 'application/octet-stream');
        $this->header('Content-Disposition', 'attachment; filename="' . $name . '"');
        $this->header('Content-Length', (string)filesize($path));
        $this->withHeaders($headers);
        $this->content = file_get_contents($path);
        
        return $this;
    }

    /**
     * Stream файла (inline)
     */
    public function file(string $path, array $headers = []): self
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }

        $this->header('Content-Type', mime_content_type($path) ?: 'application/octet-stream');
        $this->header('Content-Length', (string)filesize($path));
        $this->withHeaders($headers);
        $this->content = file_get_contents($path);
        
        return $this;
    }

    /**
     * No content (204)
     */
    public function noContent(array $headers = []): self
    {
        $this->status(self::HTTP_NO_CONTENT);
        $this->withHeaders($headers);
        $this->content = '';
        return $this;
    }

    /**
     * Установить cookie
     */
    public function cookie(
        string $name,
        string $value = '',
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true,
        string $samesite = 'Lax'
    ): self {
        Cookie::set($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite);
        return $this;
    }

    /**
     * Удалить cookie
     */
    public function withoutCookie(string $name, string $path = '/', string $domain = ''): self
    {
        Cookie::delete($name, $path, $domain);
        return $this;
    }

    /**
     * Отправить ответ
     */
    public function send(): void
    {
        // Устанавливаем статус код
        http_response_code($this->statusCode);

        // Отправляем заголовки
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        // Отправляем контент
        echo $this->content;
    }

    /**
     * Создать новый экземпляр Response
     */
    public static function make(mixed $content = '', int $status = 200, array $headers = []): self
    {
        $response = new self();
        $response->status($status);
        $response->withHeaders($headers);
        $response->content = $content;
        return $response;
    }

    /**
     * Быстрые статические методы
     */
    public static function jsonResponse(mixed $data, int $status = 200, array $headers = []): self
    {
        return (new self())->json($data, $status, $headers);
    }

    public static function htmlResponse(string $content, int $status = 200, array $headers = []): self
    {
        return (new self())->html($content, $status, $headers);
    }

    public static function redirectTo(string $url, int $status = 302, array $headers = []): self
    {
        return (new self())->redirect($url, $status, $headers);
    }

    public static function downloadFile(string $path, ?string $name = null, array $headers = []): self
    {
        return (new self())->download($path, $name, $headers);
    }

    /**
     * View response (render template)
     */
    public function view(string $template, array $data = [], int $status = 200, array $headers = []): self
    {
        $content = view($template, $data);
        return $this->html($content, $status, $headers);
    }
}

