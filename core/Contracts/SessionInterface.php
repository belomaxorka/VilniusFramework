<?php declare(strict_types=1);

namespace Core\Contracts;

/**
 * Session Manager Interface
 * 
 * Определяет полный контракт для работы с сессиями
 */
interface SessionInterface
{
    // ========== Управление сессией ==========
    
    /**
     * Запустить сессию
     */
    public function start(array $options = []): bool;

    /**
     * Проверить, запущена ли сессия
     */
    public function isStarted(): bool;

    /**
     * Получить ID сессии
     */
    public function id(): string;

    /**
     * Установить ID сессии
     */
    public function setId(string $id): void;

    /**
     * Получить имя сессии
     */
    public function name(): string;

    /**
     * Установить имя сессии
     */
    public function setName(string $name): void;

    /**
     * Регенерировать ID сессии
     */
    public function regenerate(bool $deleteOldSession = true): bool;

    /**
     * Уничтожить сессию полностью
     */
    public function destroy(): bool;

    /**
     * Сохранить и закрыть сессию
     */
    public function save(): void;

    // ========== Работа с данными ==========
    
    /**
     * Получить значение из сессии
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Установить значение в сессию
     */
    public function set(string $key, mixed $value): void;

    /**
     * Проверить существование ключа в сессии
     */
    public function has(string $key): bool;

    /**
     * Удалить значение из сессии
     */
    public function delete(string $key): void;

    /**
     * Получить все данные сессии
     */
    public function all(): array;

    /**
     * Очистить все данные сессии
     */
    public function clear(): void;

    /**
     * Получить значение и удалить его
     */
    public function pull(string $key, mixed $default = null): mixed;

    /**
     * Добавить значение в массив
     */
    public function push(string $key, mixed $value): void;

    /**
     * Увеличить числовое значение
     */
    public function increment(string $key, int $amount = 1): int;

    /**
     * Уменьшить числовое значение
     */
    public function decrement(string $key, int $amount = 1): int;

    /**
     * Получить или установить значение (lazy)
     */
    public function remember(string $key, callable $callback): mixed;

    // ========== Flash сообщения ==========
    
    /**
     * Установить flash сообщение
     */
    public function flash(string $key, mixed $value): void;

    /**
     * Получить flash сообщение (и удалить)
     */
    public function getFlash(string $key, mixed $default = null): mixed;

    /**
     * Проверить существование flash сообщения
     */
    public function hasFlash(string $key): bool;

    /**
     * Получить все flash сообщения
     */
    public function getAllFlash(): array;

    // ========== CSRF защита ==========
    
    /**
     * Генерировать CSRF токен
     */
    public function generateCsrfToken(): string;

    /**
     * Получить CSRF токен
     */
    public function getCsrfToken(): ?string;

    /**
     * Проверить CSRF токен
     */
    public function verifyCsrfToken(string $token): bool;

    // ========== Previous URL ==========
    
    /**
     * Установить предыдущий URL
     */
    public function setPreviousUrl(string $url): void;

    /**
     * Получить предыдущий URL
     */
    public function getPreviousUrl(string $default = '/'): string;

    // ========== Cookie параметры ==========
    
    /**
     * Получить параметры cookie сессии
     */
    public function getCookieParams(): array;

    /**
     * Установить параметры cookie сессии
     */
    public function setCookieParams(
        int $lifetime,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true,
        string $samesite = 'Lax'
    ): void;
}
