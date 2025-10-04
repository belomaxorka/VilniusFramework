<?php declare(strict_types=1);

namespace Core\Contracts;

/**
 * Session Manager Interface
 * 
 * Определяет контракт для работы с сессиями
 */
interface SessionInterface
{
    /**
     * Запустить сессию
     */
    public function start(array $options = []): bool;

    /**
     * Проверить, запущена ли сессия
     */
    public function isStarted(): bool;

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
     * Уничтожить сессию полностью
     */
    public function destroy(): bool;

    /**
     * Регенерировать ID сессии
     */
    public function regenerate(bool $deleteOldSession = true): bool;

    /**
     * Получить ID сессии
     */
    public function id(): string;

    /**
     * Flash сообщение
     */
    public function flash(string $key, mixed $value): void;

    /**
     * Получить flash сообщение
     */
    public function getFlash(string $key, mixed $default = null): mixed;

    /**
     * Проверить существование flash сообщения
     */
    public function hasFlash(string $key): bool;

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
}

