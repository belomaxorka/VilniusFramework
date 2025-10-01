<?php declare(strict_types=1);

namespace Core\DebugToolbar;

/**
 * Интерфейс для коллекторов Debug Toolbar
 */
interface CollectorInterface
{
    /**
     * Получить имя коллектора (уникальный идентификатор)
     */
    public function getName(): string;

    /**
     * Получить заголовок вкладки
     */
    public function getTitle(): string;

    /**
     * Получить иконку для вкладки (emoji или HTML)
     */
    public function getIcon(): string;

    /**
     * Получить значение badge (null если не нужен)
     */
    public function getBadge(): ?string;

    /**
     * Получить приоритет отображения (меньше = раньше, по умолчанию 100)
     */
    public function getPriority(): int;

    /**
     * Собрать данные коллектора
     */
    public function collect(): void;

    /**
     * Рендерить содержимое вкладки
     */
    public function render(): string;

    /**
     * Получить данные для header toolbar (статистика в шапке)
     * Возвращает массив ['label' => string, 'value' => string, 'color' => string]
     */
    public function getHeaderStats(): array;

    /**
     * Проверить, должен ли коллектор быть активным
     */
    public function isEnabled(): bool;
}
