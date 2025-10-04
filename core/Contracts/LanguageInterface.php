<?php declare(strict_types=1);

namespace Core\Contracts;

/**
 * Language Interface
 * 
 * Определяет контракт для работы с многоязычностью
 */
interface LanguageInterface
{
    /**
     * Инициализировать систему языков из конфигурации
     */
    public function init(): void;

    /**
     * Установить текущий язык и загрузить переводы
     */
    public function setLang(?string $lang = null, bool $validate = false): bool;

    /**
     * Получить перевод по ключу с необязательными плейсхолдерами
     */
    public function get(string $key, array $params = []): string;

    /**
     * Проверить существование ключа перевода
     */
    public function has(string $key): bool;

    /**
     * Получить все сообщения для текущего языка
     */
    public function all(): array;

    /**
     * Получить код текущего языка
     */
    public function getCurrentLang(): string;

    /**
     * Получить код резервного языка
     */
    public function getFallbackLang(): string;

    /**
     * Установить резервный язык
     */
    public function setFallbackLang(string $lang): void;

    /**
     * Получить все загруженные языки
     */
    public function getLoadedLanguages(): array;

    /**
     * Получить все сообщения для конкретного языка
     */
    public function getMessages(?string $lang = null): array;

    /**
     * Добавить или переопределить переводы во время выполнения
     */
    public function addMessages(string $lang, array $messages): void;

    /**
     * Проверить валидность/поддержку языка
     */
    public function isValidLanguage(string $lang): bool;

    /**
     * Получить поддерживаемые коды языков
     */
    public function getSupportedLanguages(): array;

    /**
     * Получить поддерживаемые языки с названиями
     */
    public function getSupportedLanguagesWithNames(): array;

    /**
     * Получить отображаемое название языка
     */
    public function getLanguageName(string $lang): string;

    /**
     * Получить доступные коды языков из директории lang
     */
    public function getAvailableLanguages(): array;

    /**
     * Проверить является ли язык RTL (справа налево)
     */
    public function isRTL(?string $lang = null): bool;

    /**
     * Сбросить состояние языка (для тестирования)
     */
    public function reset(): void;
}

