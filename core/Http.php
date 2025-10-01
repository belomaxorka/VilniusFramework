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
     * Получить реальный метод с учетом Method Override
     * Поддерживает _method в POST и X-HTTP-Method-Override заголовок
     */
    public static function getActualMethod(): string
    {
        $method = self::getMethod();

        if ($method === 'POST') {
            // Проверяем заголовок X-HTTP-Method-Override
            $override = self::getHeader('X-HTTP-Method-Override');
            if ($override) {
                return strtoupper($override);
            }

            // Проверяем _method в POST данных
            if (isset($_POST['_method'])) {
                return strtoupper($_POST['_method']);
            }
        }

        return $method;
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
        // Если QUERY_STRING установлен, используем его
        if (isset($_SERVER['QUERY_STRING'])) {
            return $_SERVER['QUERY_STRING'];
        }
        
        // Иначе пытаемся извлечь из REQUEST_URI
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $queryString = parse_url($uri, PHP_URL_QUERY);
        
        return $queryString ?? '';
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
     * Проверить, есть ли загруженные файлы
     */
    public static function hasFiles(): bool
    {
        return !empty($_FILES);
    }

    /**
     * Получить конкретный загруженный файл
     */
    public static function getFile(string $name): ?array
    {
        return $_FILES[$name] ?? null;
    }

    /**
     * Проверить, является ли загрузка файла валидной
     */
    public static function isValidUpload(string $name): bool
    {
        if (!isset($_FILES[$name])) {
            return false;
        }

        $file = $_FILES[$name];

        // Проверяем ошибки загрузки
        if (!isset($file['error']) || is_array($file['error'])) {
            return false;
        }

        return $file['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Получить размер загруженного файла
     */
    public static function getFileSize(string $name): int
    {
        if (!isset($_FILES[$name]['size'])) {
            return 0;
        }

        return (int)$_FILES[$name]['size'];
    }

    /**
     * Получить расширение загруженного файла
     */
    public static function getFileExtension(string $name): string
    {
        if (!isset($_FILES[$name]['name'])) {
            return '';
        }

        return strtolower(pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION));
    }

    /**
     * Получить MIME тип загруженного файла
     */
    public static function getFileMimeType(string $name): string
    {
        if (!isset($_FILES[$name]['type'])) {
            return '';
        }

        return $_FILES[$name]['type'];
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
        
        // Явно проверяем application/json (без учета */*)
        if (in_array('application/json', $types)) {
            return true;
        }
        
        // Если есть */* но НЕТ text/html - значит это API клиент
        if (in_array('*/*', $types) && !in_array('text/html', $types)) {
            return true;
        }
        
        return false;
    }

    /**
     * Проверить, принимается ли HTML
     */
    public static function acceptsHtml(): bool
    {
        $types = self::getAcceptedContentTypes();
        
        // Явно проверяем text/html
        if (in_array('text/html', $types)) {
            return true;
        }
        
        // Если есть */* - считаем что браузер
        if (in_array('*/*', $types)) {
            return true;
        }
        
        return false;
    }

    /**
     * Получить Bearer токен из Authorization заголовка
     */
    public static function getBearerToken(): ?string
    {
        $header = self::getHeader('Authorization');

        if (!$header) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Получить Basic Auth credentials
     * Возвращает ['username' => '...', 'password' => '...'] или null
     */
    public static function getBasicAuth(): ?array
    {
        // Проверяем PHP_AUTH_USER (Apache)
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            return [
                'username' => $_SERVER['PHP_AUTH_USER'],
                'password' => $_SERVER['PHP_AUTH_PW'] ?? ''
            ];
        }

        // Проверяем Authorization заголовок (nginx, etc)
        $header = self::getHeader('Authorization');

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

    /**
     * Получить Content-Length
     */
    public static function getContentLength(): int
    {
        return (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    }

    /**
     * Получить Content-Type
     */
    public static function getContentType(): string
    {
        return $_SERVER['CONTENT_TYPE'] ?? '';
    }

    /**
     * Получить MIME тип из Content-Type (без charset и прочих параметров)
     */
    public static function getMimeType(): string
    {
        $contentType = self::getContentType();

        if (empty($contentType)) {
            return '';
        }

        // Отсекаем параметры (например, charset)
        $parts = explode(';', $contentType);
        return trim($parts[0]);
    }

    /**
     * Проверить, является ли запрос multipart/form-data
     */
    public static function isMultipart(): bool
    {
        $contentType = self::getContentType();
        return str_contains(strtolower($contentType), 'multipart/form-data');
    }

    /**
     * Проверить, является ли запрос application/x-www-form-urlencoded
     */
    public static function isFormUrlEncoded(): bool
    {
        return self::getMimeType() === 'application/x-www-form-urlencoded';
    }

    /**
     * Получить все данные запроса (GET + POST объединенные)
     */
    public static function all(): array
    {
        return array_merge(self::getQueryParams(), self::getPostData());
    }

    /**
     * Получить конкретное значение из GET или POST
     */
    public static function input(string $key, mixed $default = null): mixed
    {
        // Сначала проверяем POST, потом GET
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Проверить существование параметра в GET или POST
     */
    public static function has(string $key): bool
    {
        return isset($_POST[$key]) || isset($_GET[$key]);
    }

    /**
     * Получить только указанные ключи из запроса
     */
    public static function only(array $keys): array
    {
        $all = self::all();
        $result = [];

        foreach ($keys as $key) {
            if (isset($all[$key])) {
                $result[$key] = $all[$key];
            }
        }

        return $result;
    }

    /**
     * Получить все данные кроме указанных ключей
     */
    public static function except(array $keys): array
    {
        $all = self::all();

        foreach ($keys as $key) {
            unset($all[$key]);
        }

        return $all;
    }

    /**
     * Проверить, пустое ли значение параметра
     */
    public static function isEmpty(string $key): bool
    {
        $value = self::input($key);
        return empty($value);
    }

    /**
     * Проверить, заполнено ли значение параметра (не пустое)
     */
    public static function filled(string $key): bool
    {
        return !self::isEmpty($key);
    }

    /**
     * Получить Query String как массив
     */
    public static function parseQueryString(?string $queryString = null): array
    {
        $queryString = $queryString ?? self::getQueryString();

        if (empty($queryString)) {
            return [];
        }

        parse_str($queryString, $result);
        return $result;
    }

    /**
     * Построить Query String из массива
     */
    public static function buildQueryString(array $params): string
    {
        return http_build_query($params);
    }

    /**
     * Получить URL с модифицированными параметрами
     */
    public static function getUrlWithParams(array $params, bool $merge = true): string
    {
        $baseUrl = self::getBaseUrl() . self::getPath();

        if ($merge) {
            // Парсим параметры из текущего query string, а не из $_GET
            // Это позволяет корректно работать с параметрами из URI
            $currentParams = self::parseQueryString();
            $params = array_merge($currentParams, $params);
        }

        if (empty($params)) {
            return $baseUrl;
        }

        return $baseUrl . '?' . self::buildQueryString($params);
    }

    /**
     * Проверить, является ли запрос prefetch/prerender
     */
    public static function isPrefetch(): bool
    {
        $purpose = $_SERVER['HTTP_X_MOZ'] ?? $_SERVER['HTTP_PURPOSE'] ?? '';
        return in_array(strtolower($purpose), ['prefetch', 'preview']);
    }

    /**
     * Проверить, запрос от бота/crawler
     * Простая проверка по User Agent
     */
    public static function isBot(): bool
    {
        $userAgent = strtolower(self::getUserAgent());

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

    /**
     * Получить предпочитаемый язык из Accept-Language
     */
    public static function getPreferredLanguage(array $supportedLanguages = []): string
    {
        $acceptLanguage = self::getHeader('Accept-Language');

        if (empty($acceptLanguage)) {
            return $supportedLanguages[0] ?? 'en';
        }

        // Парсим Accept-Language header
        $languages = [];
        foreach (explode(',', $acceptLanguage) as $lang) {
            $parts = explode(';q=', $lang);
            $code = strtolower(trim($parts[0]));
            $quality = isset($parts[1]) ? (float)$parts[1] : 1.0;

            // Берем только первые 2 символа (en-US -> en)
            $code = substr($code, 0, 2);

            if ($code) {
                $languages[$code] = $quality;
            }
        }

        // Сортируем по качеству
        arsort($languages);

        // Если указаны поддерживаемые языки, выбираем из них
        if (!empty($supportedLanguages)) {
            foreach ($languages as $lang => $quality) {
                if (in_array($lang, $supportedLanguages)) {
                    return $lang;
                }
            }
            return $supportedLanguages[0];
        }

        // Возвращаем самый предпочитаемый
        return array_key_first($languages) ?? 'en';
    }

    /**
     * Получить все языки из Accept-Language с качеством
     */
    public static function getAcceptedLanguages(): array
    {
        $acceptLanguage = self::getHeader('Accept-Language');

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

    /**
     * Получить charset из Content-Type
     */
    public static function getCharset(): string
    {
        $contentType = self::getContentType();

        if (preg_match('/charset=([^\s;]+)/i', $contentType, $matches)) {
            return trim($matches[1], '"\'');
        }

        return 'UTF-8'; // Дефолтный
    }

    /**
     * Проверить, является ли запрос от мобильного устройства
     * Простая проверка по User Agent
     */
    public static function isMobile(): bool
    {
        $userAgent = strtolower(self::getUserAgent());

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

    /**
     * Проверить, является ли запрос безопасным (GET, HEAD, OPTIONS)
     */
    public static function isSafe(): bool
    {
        return in_array(self::getMethod(), ['GET', 'HEAD', 'OPTIONS']);
    }

    /**
     * Проверить, является ли запрос идемпотентным (GET, HEAD, PUT, DELETE, OPTIONS)
     */
    public static function isIdempotent(): bool
    {
        return in_array(self::getMethod(), ['GET', 'HEAD', 'PUT', 'DELETE', 'OPTIONS']);
    }

    /**
     * Получить ETtag если есть
     */
    public static function getEtag(): ?string
    {
        return self::getHeader('If-None-Match');
    }

    /**
     * Получить If-Modified-Since
     */
    public static function getIfModifiedSince(): ?int
    {
        $header = self::getHeader('If-Modified-Since');

        if (!$header) {
            return null;
        }

        $timestamp = strtotime($header);
        return $timestamp !== false ? $timestamp : null;
    }
}


