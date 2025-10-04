<?php declare(strict_types=1);

namespace Core\Services;

use Core\Contracts\HttpInterface;

/**
 * HTTP Service
 * 
 * Полная instance-based реализация для работы с HTTP запросами
 */
class HttpService implements HttpInterface
{
    // ========== Базовые методы запроса ==========
    
    public function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public function getActualMethod(): string
    {
        $method = $this->getMethod();

        if ($method === 'POST') {
            $override = $this->getHeader('X-HTTP-Method-Override');
            if ($override) {
                return strtoupper($override);
            }

            if (isset($_POST['_method'])) {
                return strtoupper($_POST['_method']);
            }
        }

        return $method;
    }

    public function getProtocol(): string
    {
        return $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
    }

    public function getRequestTime(): float
    {
        return $_SERVER['REQUEST_TIME_FLOAT'] ?? $_SERVER['REQUEST_TIME'] ?? microtime(true);
    }

    // ========== URI и URL методы ==========
    
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

    public function getUrlWithParams(array $params, bool $merge = true): string
    {
        $baseUrl = $this->getBaseUrl() . $this->getPath();

        if ($merge) {
            $currentParams = $this->parseQueryString();
            $params = array_merge($currentParams, $params);
        }

        if (empty($params)) {
            return $baseUrl;
        }

        return $baseUrl . '?' . $this->buildQueryString($params);
    }

    public function parseQueryString(?string $queryString = null): array
    {
        $queryString = $queryString ?? $this->getQueryString();

        if (empty($queryString)) {
            return [];
        }

        parse_str($queryString, $result);
        return $result;
    }

    public function buildQueryString(array $params): string
    {
        return http_build_query($params);
    }

    // ========== Схема, хост, порт ==========
    
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

    // ========== Проверки метода запроса ==========
    
    public function isMethod(string $method): bool
    {
        return strtoupper($method) === strtoupper($this->getMethod());
    }

    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    public function isPatch(): bool
    {
        return $this->isMethod('PATCH');
    }

    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    public function isSafe(): bool
    {
        return in_array($this->getMethod(), ['GET', 'HEAD', 'OPTIONS']);
    }

    public function isIdempotent(): bool
    {
        return in_array($this->getMethod(), ['GET', 'HEAD', 'PUT', 'DELETE', 'OPTIONS']);
    }

    // ========== Клиентская информация ==========
    
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

    // ========== Заголовки ==========
    
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
        $normalizedName = str_replace(' ', '-', ucwords(strtolower(str_replace(['-', '_'], ' ', $name))));
        return $headers[$normalizedName] ?? null;
    }

    // ========== Cookies ==========
    
    public function getCookie(string $name): ?string
    {
        return $_COOKIE[$name] ?? null;
    }

    public function getCookies(): array
    {
        return $_COOKIE ?? [];
    }

    // ========== Параметры запроса (GET/POST) ==========
    
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

    public function only(array $keys): array
    {
        $all = $this->all();
        $result = [];

        foreach ($keys as $key) {
            if (isset($all[$key])) {
                $result[$key] = $all[$key];
            }
        }

        return $result;
    }

    public function except(array $keys): array
    {
        $all = $this->all();

        foreach ($keys as $key) {
            unset($all[$key]);
        }

        return $all;
    }

    public function isEmpty(string $key): bool
    {
        $value = $this->input($key);
        return empty($value);
    }

    public function filled(string $key): bool
    {
        return !$this->isEmpty($key);
    }

    public function getQueryParams(): array
    {
        return $_GET ?? [];
    }

    public function getPostData(): array
    {
        return $_POST ?? [];
    }

    // ========== JSON методы ==========
    
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

    public function getAcceptedContentTypes(): array
    {
        $accept = $this->getHeader('Accept') ?? '';
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

    // ========== Файлы ==========
    
    public function getFiles(): array
    {
        return $_FILES ?? [];
    }

    public function hasFiles(): bool
    {
        return !empty($_FILES);
    }

    public function getFile(string $name): ?array
    {
        return $_FILES[$name] ?? null;
    }

    public function isValidUpload(string $name): bool
    {
        if (!isset($_FILES[$name])) {
            return false;
        }

        $file = $_FILES[$name];

        if (!isset($file['error']) || is_array($file['error'])) {
            return false;
        }

        return $file['error'] === UPLOAD_ERR_OK;
    }

    public function getFileSize(string $name): int
    {
        if (!isset($_FILES[$name]['size'])) {
            return 0;
        }

        return (int)$_FILES[$name]['size'];
    }

    public function getFileExtension(string $name): string
    {
        if (!isset($_FILES[$name]['name'])) {
            return '';
        }

        return strtolower(pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION));
    }

    public function getFileMimeType(string $name): string
    {
        if (!isset($_FILES[$name]['type'])) {
            return '';
        }

        return $_FILES[$name]['type'];
    }

    // ========== Специальные проверки ==========
    
    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public function isMobile(): bool
    {
        $userAgent = strtolower($this->getUserAgent());

        $mobileKeywords = [
            'mobile', 'android', 'iphone', 'ipad', 'ipod',
            'blackberry', 'windows phone', 'opera mini',
            'palm', 'symbian', 'nokia'
        ];

        foreach ($mobileKeywords as $keyword) {
            if (str_contains($userAgent, $keyword)) {
                return true;
            }
        }

        return false;
    }

    public function isBot(): bool
    {
        $userAgent = strtolower($this->getUserAgent());

        $bots = [
            'bot', 'crawler', 'spider', 'scraper',
            'googlebot', 'bingbot', 'yandexbot',
            'facebookexternalhit', 'twitterbot',
            'whatsapp', 'telegram', 'slackbot'
        ];

        foreach ($bots as $bot) {
            if (str_contains($userAgent, $bot)) {
                return true;
            }
        }

        return false;
    }

    public function isPrefetch(): bool
    {
        $purpose = $_SERVER['HTTP_X_MOZ'] ?? $_SERVER['HTTP_PURPOSE'] ?? '';
        return in_array(strtolower($purpose), ['prefetch', 'preview']);
    }

    // ========== Content Type ==========
    
    public function getContentLength(): int
    {
        return (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    }

    public function getContentType(): string
    {
        return $_SERVER['CONTENT_TYPE'] ?? '';
    }

    public function getMimeType(): string
    {
        $contentType = $this->getContentType();

        if (empty($contentType)) {
            return '';
        }

        $parts = explode(';', $contentType);
        return trim($parts[0]);
    }

    public function getCharset(): string
    {
        $contentType = $this->getContentType();

        if (preg_match('/charset=([^\s;]+)/i', $contentType, $matches)) {
            return trim($matches[1], '"\'');
        }

        return 'UTF-8';
    }

    public function isMultipart(): bool
    {
        $contentType = $this->getContentType();
        return str_contains(strtolower($contentType), 'multipart/form-data');
    }

    public function isFormUrlEncoded(): bool
    {
        return $this->getMimeType() === 'application/x-www-form-urlencoded';
    }

    // ========== Аутентификация ==========
    
    public function getBearerToken(): ?string
    {
        $header = $this->getHeader('Authorization');

        if (!$header) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function getBasicAuth(): ?array
    {
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            return [
                'username' => $_SERVER['PHP_AUTH_USER'],
                'password' => $_SERVER['PHP_AUTH_PW'] ?? ''
            ];
        }

        $header = $this->getHeader('Authorization');

        if ($header && preg_match('/Basic\s+(.*)$/i', $header, $matches)) {
            $credentials = base64_decode($matches[1]);

            if (str_contains($credentials, ':')) {
                list($username, $password) = explode(':', $credentials, 2);
                return [
                    'username' => $username,
                    'password' => $password
                ];
            }
        }

        return null;
    }

    // ========== Языки ==========
    
    public function getPreferredLanguage(array $supportedLanguages = []): string
    {
        $acceptLanguage = $this->getHeader('Accept-Language');

        if (empty($acceptLanguage)) {
            return $supportedLanguages[0] ?? 'en';
        }

        $languages = [];
        foreach (explode(',', $acceptLanguage) as $lang) {
            $parts = explode(';q=', $lang);
            $code = strtolower(trim($parts[0]));
            $quality = isset($parts[1]) ? (float)$parts[1] : 1.0;

            $code = substr($code, 0, 2);

            if ($code) {
                $languages[$code] = $quality;
            }
        }

        arsort($languages);

        if (!empty($supportedLanguages)) {
            foreach ($languages as $lang => $quality) {
                if (in_array($lang, $supportedLanguages)) {
                    return $lang;
                }
            }
            return $supportedLanguages[0];
        }

        return array_key_first($languages) ?? 'en';
    }

    public function getAcceptedLanguages(): array
    {
        $acceptLanguage = $this->getHeader('Accept-Language');

        if (empty($acceptLanguage)) {
            return [];
        }

        $languages = [];
        foreach (explode(',', $acceptLanguage) as $lang) {
            $parts = explode(';q=', $lang);
            $code = trim($parts[0]);
            $quality = isset($parts[1]) ? (float)$parts[1] : 1.0;

            if ($code) {
                $languages[$code] = $quality;
            }
        }

        arsort($languages);

        return $languages;
    }

    // ========== Кеширование ==========
    
    public function getEtag(): ?string
    {
        return $this->getHeader('If-None-Match');
    }

    public function getIfModifiedSince(): ?int
    {
        $header = $this->getHeader('If-Modified-Since');

        if (!$header) {
            return null;
        }

        $timestamp = strtotime($header);
        return $timestamp !== false ? $timestamp : null;
    }

    // ========== Raw Input ==========
    
    public function getInputData(): string
    {
        return file_get_contents('php://input') ?: '';
    }
}
