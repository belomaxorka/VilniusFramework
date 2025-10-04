<?php declare(strict_types=1);

namespace Core;

use Core\Facades\Facade;
use Core\Contracts\HttpInterface;

/**
 * HTTP Facade
 * 
 * Статический фасад для HttpService
 * Обеспечивает обратную совместимость со старым API
 * 
 * @method static string getMethod()
 * @method static string getUri()
 * @method static string getPath()
 * @method static string getQueryString()
 * @method static string getScheme()
 * @method static bool isSecure()
 * @method static string getHost()
 * @method static int getPort()
 * @method static string getFullUrl()
 * @method static string getBaseUrl()
 * @method static string getClientIp()
 * @method static string getUserAgent()
 * @method static string getReferer()
 * @method static array getHeaders()
 * @method static string|null getHeader(string $name)
 * @method static bool isAjax()
 * @method static bool isMethod(string $method)
 * @method static string|null getCookie(string $name)
 * @method static array getCookies()
 * @method static mixed getJsonData(bool $assoc = true)
 * @method static bool isJson()
 * @method static bool acceptsJson()
 * @method static bool acceptsHtml()
 * @method static array all()
 * @method static mixed input(string $key, mixed $default = null)
 * @method static bool has(string $key)
 * 
 * @see \Core\Services\HttpService
 */
class Http extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HttpInterface::class;
    }

    // Дополнительные утилитные методы которые не входят в интерфейс
    // но используются в старом коде - делегируем к сервису
    
    public static function getActualMethod(): string
    {
        $method = static::getMethod();

        if ($method === 'POST') {
            $override = static::getHeader('X-HTTP-Method-Override');
            if ($override) {
                return strtoupper($override);
            }

            if (isset($_POST['_method'])) {
                return strtoupper($_POST['_method']);
            }
        }

        return $method;
    }

    public static function getProtocol(): string
    {
        return $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
    }

    public static function getRequestTime(): float
    {
        return $_SERVER['REQUEST_TIME_FLOAT'] ?? $_SERVER['REQUEST_TIME'] ?? microtime(true);
    }

    public static function isGet(): bool
    {
        return static::isMethod('GET');
    }

    public static function isPost(): bool
    {
        return static::isMethod('POST');
    }

    public static function isPut(): bool
    {
        return static::isMethod('PUT');
    }

    public static function isPatch(): bool
    {
        return static::isMethod('PATCH');
    }

    public static function isDelete(): bool
    {
        return static::isMethod('DELETE');
    }

    public static function getQueryParams(): array
    {
        return $_GET ?? [];
    }

    public static function getPostData(): array
    {
        return $_POST ?? [];
    }

    public static function getFiles(): array
    {
        return $_FILES ?? [];
    }

    public static function hasFiles(): bool
    {
        return !empty($_FILES);
    }

    public static function getFile(string $name): ?array
    {
        return $_FILES[$name] ?? null;
    }

    public static function isValidUpload(string $name): bool
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

    public static function getFileSize(string $name): int
    {
        if (!isset($_FILES[$name]['size'])) {
            return 0;
        }

        return (int)$_FILES[$name]['size'];
    }

    public static function getFileExtension(string $name): string
    {
        if (!isset($_FILES[$name]['name'])) {
            return '';
        }

        return strtolower(pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION));
    }

    public static function getFileMimeType(string $name): string
    {
        if (!isset($_FILES[$name]['type'])) {
            return '';
        }

        return $_FILES[$name]['type'];
    }

    public static function getInputData(): string
    {
        return file_get_contents('php://input') ?: '';
    }

    public static function getAcceptedContentTypes(): array
    {
        $accept = static::getHeader('Accept') ?? '';
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

    public static function getBearerToken(): ?string
    {
        $header = static::getHeader('Authorization');

        if (!$header) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public static function getBasicAuth(): ?array
    {
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            return [
                'username' => $_SERVER['PHP_AUTH_USER'],
                'password' => $_SERVER['PHP_AUTH_PW'] ?? ''
            ];
        }

        $header = static::getHeader('Authorization');

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

    public static function getContentLength(): int
    {
        return (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    }

    public static function getContentType(): string
    {
        return $_SERVER['CONTENT_TYPE'] ?? '';
    }

    public static function getMimeType(): string
    {
        $contentType = static::getContentType();

        if (empty($contentType)) {
            return '';
        }

        $parts = explode(';', $contentType);
        return trim($parts[0]);
    }

    public static function isMultipart(): bool
    {
        $contentType = static::getContentType();
        return str_contains(strtolower($contentType), 'multipart/form-data');
    }

    public static function isFormUrlEncoded(): bool
    {
        return static::getMimeType() === 'application/x-www-form-urlencoded';
    }

    public static function only(array $keys): array
    {
        $all = static::all();
        $result = [];

        foreach ($keys as $key) {
            if (isset($all[$key])) {
                $result[$key] = $all[$key];
            }
        }

        return $result;
    }

    public static function except(array $keys): array
    {
        $all = static::all();

        foreach ($keys as $key) {
            unset($all[$key]);
        }

        return $all;
    }

    public static function isEmpty(string $key): bool
    {
        $value = static::input($key);
        return empty($value);
    }

    public static function filled(string $key): bool
    {
        return !static::isEmpty($key);
    }

    public static function parseQueryString(?string $queryString = null): array
    {
        $queryString = $queryString ?? static::getQueryString();

        if (empty($queryString)) {
            return [];
        }

        parse_str($queryString, $result);
        return $result;
    }

    public static function buildQueryString(array $params): string
    {
        return http_build_query($params);
    }

    public static function getUrlWithParams(array $params, bool $merge = true): string
    {
        $baseUrl = static::getBaseUrl() . static::getPath();

        if ($merge) {
            $currentParams = static::parseQueryString();
            $params = array_merge($currentParams, $params);
        }

        if (empty($params)) {
            return $baseUrl;
        }

        return $baseUrl . '?' . static::buildQueryString($params);
    }

    public static function isPrefetch(): bool
    {
        $purpose = $_SERVER['HTTP_X_MOZ'] ?? $_SERVER['HTTP_PURPOSE'] ?? '';
        return in_array(strtolower($purpose), ['prefetch', 'preview']);
    }

    public static function isBot(): bool
    {
        $userAgent = strtolower(static::getUserAgent());

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

    public static function getPreferredLanguage(array $supportedLanguages = []): string
    {
        $acceptLanguage = static::getHeader('Accept-Language');

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

    public static function getAcceptedLanguages(): array
    {
        $acceptLanguage = static::getHeader('Accept-Language');

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

    public static function getCharset(): string
    {
        $contentType = static::getContentType();

        if (preg_match('/charset=([^\s;]+)/i', $contentType, $matches)) {
            return trim($matches[1], '"\'');
        }

        return 'UTF-8';
    }

    public static function isMobile(): bool
    {
        $userAgent = strtolower(static::getUserAgent());

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

    public static function isSafe(): bool
    {
        return in_array(static::getMethod(), ['GET', 'HEAD', 'OPTIONS']);
    }

    public static function isIdempotent(): bool
    {
        return in_array(static::getMethod(), ['GET', 'HEAD', 'PUT', 'DELETE', 'OPTIONS']);
    }

    public static function getEtag(): ?string
    {
        return static::getHeader('If-None-Match');
    }

    public static function getIfModifiedSince(): ?int
    {
        $header = static::getHeader('If-Modified-Since');

        if (!$header) {
            return null;
        }

        $timestamp = strtotime($header);
        return $timestamp !== false ? $timestamp : null;
    }
}
