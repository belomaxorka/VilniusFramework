<?php declare(strict_types=1);

namespace Core;

/**
 * HTTP Request
 * 
 * ООП обертка над HTTP запросом
 */
class Request
{
    protected static ?Request $instance = null;
    protected array $query = [];
    protected array $post = [];
    protected array $files = [];
    protected array $cookies = [];
    protected array $server = [];
    protected ?string $content = null;

    public function __construct()
    {
        $this->query = $_GET ?? [];
        $this->post = $_POST ?? [];
        $this->files = $_FILES ?? [];
        $this->cookies = $_COOKIE ?? [];
        $this->server = $_SERVER ?? [];
    }

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
     * Создать из глобальных переменных
     */
    public static function capture(): self
    {
        return self::getInstance();
    }

    /**
     * Получить метод запроса
     */
    public function method(): string
    {
        return Http::getMethod();
    }

    /**
     * Получить актуальный метод (с учетом _method override)
     */
    public function getActualMethod(): string
    {
        return Http::getActualMethod();
    }

    /**
     * Проверить метод
     */
    public function isMethod(string $method): bool
    {
        return Http::isMethod($method);
    }

    /**
     * Получить URI
     */
    public function uri(): string
    {
        return Http::getUri();
    }

    /**
     * Получить путь
     */
    public function path(): string
    {
        return Http::getPath();
    }

    /**
     * Получить полный URL
     */
    public function url(): string
    {
        return Http::getFullUrl();
    }

    /**
     * Получить значение из запроса (GET или POST)
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return Http::input($key, $default);
    }

    /**
     * Получить все данные запроса
     */
    public function all(): array
    {
        return Http::all();
    }

    /**
     * Получить только указанные ключи
     */
    public function only(array $keys): array
    {
        return Http::only($keys);
    }

    /**
     * Получить все кроме указанных ключей
     */
    public function except(array $keys): array
    {
        return Http::except($keys);
    }

    /**
     * Проверить наличие параметра
     */
    public function has(string $key): bool
    {
        return Http::has($key);
    }

    /**
     * Проверить наличие нескольких параметров
     */
    public function hasAll(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Проверить наличие хотя бы одного параметра
     */
    public function hasAny(array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->has($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Проверить, заполнен ли параметр
     */
    public function filled(string $key): bool
    {
        return Http::filled($key);
    }

    /**
     * Получить query параметры
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    /**
     * Получить POST данные
     */
    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    /**
     * Получить JSON данные
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        $data = Http::getJsonData(true);
        
        if ($key === null) {
            return $data;
        }
        
        return $data[$key] ?? $default;
    }

    /**
     * Получить raw input
     */
    public function getContent(): string
    {
        if ($this->content === null) {
            $this->content = Http::getInputData();
        }
        return $this->content;
    }

    /**
     * Получить заголовок
     */
    public function header(string $key, ?string $default = null): ?string
    {
        return Http::getHeader($key) ?? $default;
    }

    /**
     * Получить все заголовки
     */
    public function headers(): array
    {
        return Http::getHeaders();
    }

    /**
     * Получить Bearer токен
     */
    public function bearerToken(): ?string
    {
        return Http::getBearerToken();
    }

    /**
     * Получить IP адрес
     */
    public function ip(): string
    {
        return Http::getClientIp();
    }

    /**
     * Получить User Agent
     */
    public function userAgent(): string
    {
        return Http::getUserAgent();
    }

    /**
     * Получить cookie
     */
    public function cookie(string $key, ?string $default = null): ?string
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Получить все cookies
     */
    public function cookies(): array
    {
        return $this->cookies;
    }

    /**
     * Получить файл
     */
    public function file(string $key): ?array
    {
        return Http::getFile($key);
    }

    /**
     * Получить все файлы
     */
    public function files(): array
    {
        return $this->files;
    }

    /**
     * Проверить наличие файла
     */
    public function hasFile(string $key): bool
    {
        return Http::isValidUpload($key);
    }

    /**
     * Проверки типа запроса
     */
    public function isJson(): bool
    {
        return Http::isJson();
    }

    public function acceptsJson(): bool
    {
        return Http::acceptsJson();
    }

    public function acceptsHtml(): bool
    {
        return Http::acceptsHtml();
    }

    public function isAjax(): bool
    {
        return Http::isAjax();
    }

    public function isSecure(): bool
    {
        return Http::isSecure();
    }

    public function isMobile(): bool
    {
        return Http::isMobile();
    }

    public function isBot(): bool
    {
        return Http::isBot();
    }

    /**
     * Определить предпочтительный формат ответа
     */
    public function prefers(array $formats): ?string
    {
        $acceptedTypes = Http::getAcceptedContentTypes();
        
        foreach ($acceptedTypes as $type) {
            foreach ($formats as $format) {
                if ($this->matchesType($type, $format)) {
                    return $format;
                }
            }
        }
        
        return null;
    }

    /**
     * Проверить, хочет ли клиент JSON
     */
    public function wantsJson(): bool
    {
        return $this->acceptsJson() || $this->prefers(['json', 'html']) === 'json';
    }

    /**
     * Проверить соответствие типа
     */
    protected function matchesType(string $actual, string $expected): bool
    {
        $map = [
            'json' => 'application/json',
            'html' => 'text/html',
            'xml' => 'application/xml',
            'text' => 'text/plain',
        ];
        
        $expectedType = $map[$expected] ?? $expected;
        return str_contains($actual, $expectedType);
    }

    /**
     * Получить referer
     */
    public function referer(): string
    {
        return Http::getReferer();
    }

    /**
     * Получить схему (http/https)
     */
    public function scheme(): string
    {
        return Http::getScheme();
    }

    /**
     * Получить хост
     */
    public function host(): string
    {
        return Http::getHost();
    }

    /**
     * Получить порт
     */
    public function port(): int
    {
        return Http::getPort();
    }

    /**
     * Мерж данных в запрос
     */
    public function merge(array $input): self
    {
        $this->post = array_merge($this->post, $input);
        return $this;
    }

    /**
     * Заменить данные запроса
     */
    public function replace(array $input): self
    {
        $this->post = $input;
        return $this;
    }

    /**
     * Magic get
     */
    public function __get(string $name): mixed
    {
        return $this->input($name);
    }

    /**
     * Magic isset
     */
    public function __isset(string $name): bool
    {
        return $this->has($name);
    }
}

