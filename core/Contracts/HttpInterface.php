<?php declare(strict_types=1);

namespace Core\Contracts;

/**
 * HTTP Service Interface
 * 
 * Определяет полный контракт для работы с HTTP запросами
 */
interface HttpInterface
{
    // ========== Базовые методы запроса ==========
    
    /**
     * Получить метод HTTP-запроса (GET, POST, PUT, DELETE, etc.)
     */
    public function getMethod(): string;

    /**
     * Получить актуальный метод с учетом HTTP Method Spoofing
     * (_method parameter или X-HTTP-Method-Override header)
     */
    public function getActualMethod(): string;

    /**
     * Получить протокол (HTTP/1.1, HTTP/2, etc.)
     */
    public function getProtocol(): string;

    /**
     * Получить время начала запроса
     */
    public function getRequestTime(): float;

    // ========== URI и URL методы ==========
    
    /**
     * Получить URI запроса (с query string)
     */
    public function getUri(): string;

    /**
     * Получить путь без query string
     */
    public function getPath(): string;

    /**
     * Получить query string
     */
    public function getQueryString(): string;

    /**
     * Получить полный URL текущего запроса
     */
    public function getFullUrl(): string;

    /**
     * Получить базовый URL (без URI)
     */
    public function getBaseUrl(): string;

    /**
     * Построить URL с параметрами
     */
    public function getUrlWithParams(array $params, bool $merge = true): string;

    /**
     * Парсить query string в массив
     */
    public function parseQueryString(?string $queryString = null): array;

    /**
     * Построить query string из массива
     */
    public function buildQueryString(array $params): string;

    // ========== Схема, хост, порт ==========
    
    /**
     * Получить схему (http или https)
     */
    public function getScheme(): string;

    /**
     * Проверить, является ли запрос HTTPS
     */
    public function isSecure(): bool;

    /**
     * Получить хост
     */
    public function getHost(): string;

    /**
     * Получить порт
     */
    public function getPort(): int;

    // ========== Проверки метода запроса ==========
    
    /**
     * Проверить метод запроса
     */
    public function isMethod(string $method): bool;

    /**
     * Проверить GET метод
     */
    public function isGet(): bool;

    /**
     * Проверить POST метод
     */
    public function isPost(): bool;

    /**
     * Проверить PUT метод
     */
    public function isPut(): bool;

    /**
     * Проверить PATCH метод
     */
    public function isPatch(): bool;

    /**
     * Проверить DELETE метод
     */
    public function isDelete(): bool;

    /**
     * Проверить безопасный метод (GET, HEAD, OPTIONS)
     */
    public function isSafe(): bool;

    /**
     * Проверить идемпотентный метод (GET, HEAD, PUT, DELETE, OPTIONS)
     */
    public function isIdempotent(): bool;

    // ========== Клиентская информация ==========
    
    /**
     * Получить IP-адрес клиента
     */
    public function getClientIp(): string;

    /**
     * Получить User Agent
     */
    public function getUserAgent(): string;

    /**
     * Получить Referer
     */
    public function getReferer(): string;

    // ========== Заголовки ==========
    
    /**
     * Получить все HTTP заголовки
     */
    public function getHeaders(): array;

    /**
     * Получить конкретный заголовок
     */
    public function getHeader(string $name): ?string;

    // ========== Cookies ==========
    
    /**
     * Получить конкретную куку
     */
    public function getCookie(string $name): ?string;

    /**
     * Получить все cookies
     */
    public function getCookies(): array;

    // ========== Параметры запроса (GET/POST) ==========
    
    /**
     * Получить все данные запроса (GET + POST объединенные)
     */
    public function all(): array;

    /**
     * Получить конкретное значение из GET или POST
     */
    public function input(string $key, mixed $default = null): mixed;

    /**
     * Проверить существование параметра в GET или POST
     */
    public function has(string $key): bool;

    /**
     * Получить только указанные ключи
     */
    public function only(array $keys): array;

    /**
     * Получить все кроме указанных ключей
     */
    public function except(array $keys): array;

    /**
     * Проверить что значение пустое
     */
    public function isEmpty(string $key): bool;

    /**
     * Проверить что значение заполнено
     */
    public function filled(string $key): bool;

    /**
     * Получить GET параметры
     */
    public function getQueryParams(): array;

    /**
     * Получить POST данные
     */
    public function getPostData(): array;

    // ========== JSON методы ==========
    
    /**
     * Получить JSON данные из input
     */
    public function getJsonData(bool $assoc = true): mixed;

    /**
     * Проверить, содержит ли Content-Type JSON
     */
    public function isJson(): bool;

    /**
     * Проверить, принимается ли JSON
     */
    public function acceptsJson(): bool;

    /**
     * Проверить, принимается ли HTML
     */
    public function acceptsHtml(): bool;

    /**
     * Получить принимаемые типы контента
     */
    public function getAcceptedContentTypes(): array;

    // ========== Файлы ==========
    
    /**
     * Получить все файлы
     */
    public function getFiles(): array;

    /**
     * Проверить наличие файлов
     */
    public function hasFiles(): bool;

    /**
     * Получить конкретный файл
     */
    public function getFile(string $name): ?array;

    /**
     * Проверить валидность загрузки файла
     */
    public function isValidUpload(string $name): bool;

    /**
     * Получить размер файла
     */
    public function getFileSize(string $name): int;

    /**
     * Получить расширение файла
     */
    public function getFileExtension(string $name): string;

    /**
     * Получить MIME тип файла
     */
    public function getFileMimeType(string $name): string;

    // ========== Специальные проверки ==========
    
    /**
     * Проверить, является ли запрос AJAX
     */
    public function isAjax(): bool;

    /**
     * Проверить является ли клиент мобильным
     */
    public function isMobile(): bool;

    /**
     * Проверить является ли клиент ботом
     */
    public function isBot(): bool;

    /**
     * Проверить является ли запрос prefetch
     */
    public function isPrefetch(): bool;

    // ========== Content Type ==========
    
    /**
     * Получить длину контента
     */
    public function getContentLength(): int;

    /**
     * Получить Content-Type
     */
    public function getContentType(): string;

    /**
     * Получить MIME тип
     */
    public function getMimeType(): string;

    /**
     * Получить charset
     */
    public function getCharset(): string;

    /**
     * Проверить multipart/form-data
     */
    public function isMultipart(): bool;

    /**
     * Проверить application/x-www-form-urlencoded
     */
    public function isFormUrlEncoded(): bool;

    // ========== Аутентификация ==========
    
    /**
     * Получить Bearer токен
     */
    public function getBearerToken(): ?string;

    /**
     * Получить Basic Auth данные
     */
    public function getBasicAuth(): ?array;

    // ========== Языки ==========
    
    /**
     * Получить предпочитаемый язык
     */
    public function getPreferredLanguage(array $supportedLanguages = []): string;

    /**
     * Получить принимаемые языки
     */
    public function getAcceptedLanguages(): array;

    // ========== Кеширование ==========
    
    /**
     * Получить ETag
     */
    public function getEtag(): ?string;

    /**
     * Получить If-Modified-Since
     */
    public function getIfModifiedSince(): ?int;

    // ========== Raw Input ==========
    
    /**
     * Получить raw input data
     */
    public function getInputData(): string;
}
