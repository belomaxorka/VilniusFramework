<?php declare(strict_types=1);

namespace Core\Contracts;

/**
 * HTTP Service Interface
 * 
 * Определяет контракт для работы с HTTP запросами
 */
interface HttpInterface
{
    /**
     * Получить метод HTTP-запроса
     */
    public function getMethod(): string;

    /**
     * Получить URI запроса
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

    /**
     * Получить полный URL текущего запроса
     */
    public function getFullUrl(): string;

    /**
     * Получить базовый URL (без URI)
     */
    public function getBaseUrl(): string;

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

    /**
     * Получить все HTTP заголовки
     */
    public function getHeaders(): array;

    /**
     * Получить конкретный заголовок
     */
    public function getHeader(string $name): ?string;

    /**
     * Проверить, является ли запрос AJAX
     */
    public function isAjax(): bool;

    /**
     * Проверить метод запроса
     */
    public function isMethod(string $method): bool;

    /**
     * Получить конкретную куку
     */
    public function getCookie(string $name): ?string;

    /**
     * Получить все cookies
     */
    public function getCookies(): array;

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
}

