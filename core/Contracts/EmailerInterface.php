<?php declare(strict_types=1);

namespace Core\Contracts;

use Core\Emailer\EmailMessage;
use Core\Emailer\EmailDriverInterface;

/**
 * Emailer Interface
 * 
 * Определяет контракт для работы с отправкой email
 */
interface EmailerInterface
{
    /**
     * Инициализировать emailer из конфигурации
     */
    public function init(): void;

    /**
     * Установить драйвер email
     */
    public function setDriver(EmailDriverInterface $driver): void;

    /**
     * Получить текущий драйвер
     */
    public function getDriver(): ?EmailDriverInterface;

    /**
     * Отправить email сообщение
     */
    public function send(EmailMessage $message): bool;

    /**
     * Создать новое email сообщение
     */
    public function message(): EmailMessage;

    /**
     * Быстрая отправка email
     */
    public function sendTo(string $to, string $subject, string $body, bool $isHtml = true): bool;

    /**
     * Отправить email используя view шаблон
     */
    public function sendView(string $to, string $subject, string $view, array $data = []): bool;

    /**
     * Получить историю отправленных email
     */
    public function getSentEmails(): array;

    /**
     * Получить статистику
     */
    public function getStats(): array;

    /**
     * Очистить историю отправленных email (для тестирования)
     */
    public function clearHistory(): void;

    /**
     * Сбросить emailer (для тестирования)
     */
    public function reset(): void;
}

