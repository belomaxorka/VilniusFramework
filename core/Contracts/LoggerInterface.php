<?php declare(strict_types=1);

namespace Core\Contracts;

/**
 * Logger Service Interface
 * 
 * Определяет контракт для сервиса логирования
 */
interface LoggerInterface
{
    /**
     * Инициализация логгера
     */
    public function init(): void;

    /**
     * Логировать сообщение с указанным уровнем
     */
    public function log(string $level, string $message, array $context = []): void;

    /**
     * Логирование уровня DEBUG
     */
    public function debug(string $message, array $context = []): void;

    /**
     * Логирование уровня INFO
     */
    public function info(string $message, array $context = []): void;

    /**
     * Логирование уровня WARNING
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Логирование уровня ERROR
     */
    public function error(string $message, array $context = []): void;

    /**
     * Логирование критических ошибок
     */
    public function critical(string $message, array $context = []): void;

    /**
     * Установить минимальный уровень логирования
     */
    public function setMinLevel(string $level): void;

    /**
     * Получить текущий минимальный уровень
     */
    public function getMinLevel(): string;

    /**
     * Получить все логи текущего запроса
     */
    public function getLogs(): array;

    /**
     * Получить статистику по логам
     */
    public function getStats(): array;

    /**
     * Очистить логи
     */
    public function clearLogs(): void;
}

