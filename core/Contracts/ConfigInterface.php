<?php declare(strict_types=1);

namespace Core\Contracts;

/**
 * Configuration Repository Interface
 * 
 * Определяет контракт для работы с конфигурацией приложения
 */
interface ConfigInterface
{
    /**
     * Загрузить конфигурацию из директории
     */
    public function load(string $path, ?string $environment = null, bool $recursive = false): void;

    /**
     * Загрузить конфигурацию из файла
     */
    public function loadFile(string $filePath): void;

    /**
     * Получить значение конфигурации с поддержкой dot notation
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Установить значение конфигурации
     */
    public function set(string $key, mixed $value): void;

    /**
     * Проверить существование ключа
     */
    public function has(string $key): bool;

    /**
     * Удалить ключ конфигурации
     */
    public function forget(string $key): void;

    /**
     * Получить все данные конфигурации
     */
    public function all(): array;

    /**
     * Очистить всю конфигурацию
     */
    public function clear(): void;

    /**
     * Получить обязательное значение (выбрасывает исключение если не найдено)
     */
    public function getRequired(string $key): mixed;

    /**
     * Получить несколько значений одновременно
     */
    public function getMany(array $keys, mixed $default = null): array;

    /**
     * Добавить значение в массив конфигурации
     */
    public function push(string $key, mixed $value): void;

    /**
     * Разрешить значение (выполнить callable если это macro)
     */
    public function resolve(string $key, mixed $default = null): mixed;

    /**
     * Заблокировать конфигурацию от изменений
     */
    public function lock(): void;

    /**
     * Разблокировать конфигурацию
     */
    public function unlock(): void;

    /**
     * Проверить, заблокирована ли конфигурация
     */
    public function isLocked(): bool;

    /**
     * Кешировать конфигурацию в файл
     */
    public function cache(string $cachePath): bool;

    /**
     * Загрузить конфигурацию из кеша
     */
    public function loadCached(string $cachePath): bool;

    /**
     * Проверить, загружена ли конфигурация из кеша
     */
    public function isLoadedFromCache(): bool;

    /**
     * Проверить существование кеша
     */
    public function isCached(string $cachePath): bool;

    /**
     * Очистить кеш
     */
    public function clearCache(string $cachePath): bool;

    /**
     * Получить информацию о кеше
     */
    public function getCacheInfo(string $cachePath): ?array;
}

