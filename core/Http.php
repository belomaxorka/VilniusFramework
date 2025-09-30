<?php declare(strict_types=1);

namespace Core;

/**
 * Утилитный класс для работы с HTTP-запросами
 */
class Http
{
    /**
     * Получить метод HTTP-запроса
     */
    public static function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Получить URI запроса
     */
    public static function getUri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    /**
     * Получить путь без query string
     */
    public static function getPath(): string
    {
        return parse_url(self::getUri(), PHP_URL_PATH) ?? '/';
    }

    /**
     * Получить query string
     */
    public static function getQueryString(): string
    {
        return $_SERVER['QUERY_STRING'] ?? '';
    }

    /**
     * Получить протокол (HTTP/1.1, HTTP/2, etc.)
     */
    public static function getProtocol(): string
    {
        return $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
    }

    /**
     * Получить схему (http или https)
     */
    public static function getScheme(): string
    {
        if (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || self::getPort() == 443
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        ) {
            return 'https';
        }
        return 'http';
    }

    /**
     * Проверить, является ли запрос HTTPS
     */
    public static function isSecure(): bool
    {
        return self::getScheme() === 'https';
    }

    /**
     * Получить хост
     */
    public static function getHost(): string
    {
        return $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    }

    /**
     * Получить порт
     */
    public static function getPort(): int
    {
        return (int)($_SERVER['SERVER_PORT'] ?? 80);
    }

    /**
     * Получить полный URL текущего запроса
     */
    public static function getFullUrl(): string
    {
        $url = self::getScheme() . '://' . self::getHost();

        $port = self::getPort();
        if (
            (self::getScheme() === 'http' && $port != 80)
            || (self::getScheme() === 'https' && $port != 443)
        ) {
            $url .= ':' . $port;
        }

        $url .= self::getUri();

        return $url;
    }

    /**
     * Получить базовый URL (без URI)
     */
    public static function getBaseUrl(): string
    {
        $url = self::getScheme() . '://' . self::getHost();

        $port = self::getPort();
        if (
            (self::getScheme() === 'http' && $port != 80)
            || (self::getScheme() === 'https' && $port != 443)
        ) {
            $url .= ':' . $port;
        }

        return $url;
    }

    /**
     * Получить IP-адрес клиента
     */
    public static function getClientIp(): string
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

    /**
     * Получить User Agent
     */
    public static function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Получить Referer
     */
    public static function getReferer(): string
    {
        return $_SERVER['HTTP_REFERER'] ?? '';
    }

    /**
     * Получить время начала запроса
     */
    public static function getRequestTime(): float
    {
        return $_SERVER['REQUEST_TIME_FLOAT'] ?? $_SERVER['REQUEST_TIME'] ?? microtime(true);
    }

    /**
     * Получить все HTTP заголовки
     */
    public static function getHeaders(): array
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

    /**
     * Получить конкретный заголовок
     */
    public static function getHeader(string $name): ?string
    {
        $headers = self::getHeaders();
        
        // Нормализуем имя заголовка
        $normalizedName = str_replace(' ', '-', ucwords(strtolower(str_replace(['-', '_'], ' ', $name))));
        
        return $headers[$normalizedName] ?? null;
    }

    /**
     * Проверить, является ли запрос AJAX
     */
    public static function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Проверить метод запроса
     */
    public static function isMethod(string $method): bool
    {
        return strtoupper($method) === strtoupper(self::getMethod());
    }

    /**
     * Проверить, является ли GET запросом
     */
    public static function isGet(): bool
    {
        return self::isMethod('GET');
    }

    /**
     * Проверить, является ли POST запросом
     */
    public static function isPost(): bool
    {
        return self::isMethod('POST');
    }

    /**
     * Проверить, является ли PUT запросом
     */
    public static function isPut(): bool
    {
        return self::isMethod('PUT');
    }

    /**
     * Проверить, является ли PATCH запросом
     */
    public static function isPatch(): bool
    {
        return self::isMethod('PATCH');
    }

    /**
     * Проверить, является ли DELETE запросом
     */
    public static function isDelete(): bool
    {
        return self::isMethod('DELETE');
    }

    /**
     * Получить GET параметры
     */
    public static function getQueryParams(): array
    {
        return $_GET ?? [];
    }

    /**
     * Получить POST данные
     */
    public static function getPostData(): array
    {
        return $_POST ?? [];
    }

    /**
     * Получить загруженные файлы
     */
    public static function getFiles(): array
    {
        return $_FILES ?? [];
    }

    /**
     * Получить куки
     */
    public static function getCookies(): array
    {
        return $_COOKIE ?? [];
    }

    /**
     * Получить конкретную куку
     */
    public static function getCookie(string $name): ?string
    {
        return $_COOKIE[$name] ?? null;
    }

    /**
     * Получить данные из input (php://input)
     * Полезно для JSON запросов
     */
    public static function getInputData(): string
    {
        return file_get_contents('php://input') ?: '';
    }

    /**
     * Получить JSON данные из input и декодировать
     */
    public static function getJsonData(bool $assoc = true): mixed
    {
        $input = self::getInputData();
        if (empty($input)) {
            return $assoc ? [] : null;
        }

        return json_decode($input, $assoc);
    }

    /**
     * Проверить, содержит ли Content-Type JSON
     */
    public static function isJson(): bool
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return str_contains(strtolower($contentType), 'application/json');
    }

    /**
     * Получить принимаемые типы контента (Accept header)
     */
    public static function getAcceptedContentTypes(): array
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (empty($accept)) {
            return [];
        }

        $types = [];
        foreach (explode(',', $accept) as $type) {
            $type = trim(explode(';', $type)[0]);
            if ($type) {
                $types[] = $type;
            }
        }

        return $types;
    }

    /**
     * Проверить, принимается ли JSON
     */
    public static function acceptsJson(): bool
    {
        $types = self::getAcceptedContentTypes();
        return in_array('application/json', $types) || in_array('*/*', $types);
    }

    /**
     * Проверить, принимается ли HTML
     */
    public static function acceptsHtml(): bool
    {
        $types = self::getAcceptedContentTypes();
        return in_array('text/html', $types) || in_array('*/*', $types);
    }
}

