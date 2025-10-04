<?php declare(strict_types=1);

namespace Core\Services;

use Core\Contracts\HttpInterface;

/**
 * HTTP Service
 * 
 * Instance-based реализация для работы с HTTP запросами
 */
class HttpService implements HttpInterface
{
    public function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public function getUri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    public function getPath(): string
    {
        return parse_url($this->getUri(), PHP_URL_PATH) ?? '/';
    }

    public function getQueryString(): string
    {
        if (isset($_SERVER['QUERY_STRING'])) {
            return $_SERVER['QUERY_STRING'];
        }
        
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $queryString = parse_url($uri, PHP_URL_QUERY);
        
        return $queryString ?? '';
    }

    public function getScheme(): string
    {
        if (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $this->getPort() == 443
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        ) {
            return 'https';
        }
        return 'http';
    }

    public function isSecure(): bool
    {
        return $this->getScheme() === 'https';
    }

    public function getHost(): string
    {
        return $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    }

    public function getPort(): int
    {
        return (int)($_SERVER['SERVER_PORT'] ?? 80);
    }

    public function getFullUrl(): string
    {
        $url = $this->getScheme() . '://' . $this->getHost();

        $port = $this->getPort();
        if (
            ($this->getScheme() === 'http' && $port != 80)
            || ($this->getScheme() === 'https' && $port != 443)
        ) {
            $url .= ':' . $port;
        }

        $url .= $this->getUri();

        return $url;
    }

    public function getBaseUrl(): string
    {
        $url = $this->getScheme() . '://' . $this->getHost();

        $port = $this->getPort();
        if (
            ($this->getScheme() === 'http' && $port != 80)
            || ($this->getScheme() === 'https' && $port != 443)
        ) {
            $url .= ':' . $port;
        }

        return $url;
    }

    public function getClientIp(): string
    {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }

    public function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function getReferer(): string
    {
        return $_SERVER['HTTP_REFERER'] ?? '';
    }

    public function getHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders() ?: [];
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace(
                    ' ',
                    '-',
                    ucwords(strtolower(str_replace('_', ' ', substr($key, 5))))
                );
                $headers[$header] = $value;
            }
        }

        return $headers;
    }

    public function getHeader(string $name): ?string
    {
        $headers = $this->getHeaders();

        // Нормализуем имя заголовка
        $normalizedName = str_replace(' ', '-', ucwords(strtolower(str_replace(['-', '_'], ' ', $name))));

        return $headers[$normalizedName] ?? null;
    }

    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($method) === strtoupper($this->getMethod());
    }

    public function getCookie(string $name): ?string
    {
        return $_COOKIE[$name] ?? null;
    }

    public function getCookies(): array
    {
        return $_COOKIE ?? [];
    }

    public function getJsonData(bool $assoc = true): mixed
    {
        $input = file_get_contents('php://input') ?: '';
        if (empty($input)) {
            return $assoc ? [] : null;
        }

        return json_decode($input, $assoc);
    }

    public function isJson(): bool
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return str_contains(strtolower($contentType), 'application/json');
    }

    public function acceptsJson(): bool
    {
        $accept = $this->getHeader('Accept') ?? '';
        
        if (str_contains($accept, 'application/json')) {
            return true;
        }
        
        if (str_contains($accept, '*/*') && !str_contains($accept, 'text/html')) {
            return true;
        }
        
        return false;
    }

    public function acceptsHtml(): bool
    {
        $accept = $this->getHeader('Accept') ?? '';
        
        if (str_contains($accept, 'text/html')) {
            return true;
        }
        
        if (str_contains($accept, '*/*')) {
            return true;
        }
        
        return false;
    }

    public function all(): array
    {
        return array_merge($_GET ?? [], $_POST ?? []);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_POST[$key]) || isset($_GET[$key]);
    }
}

