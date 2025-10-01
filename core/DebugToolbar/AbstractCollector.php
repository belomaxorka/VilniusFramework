<?php declare(strict_types=1);

namespace Core\DebugToolbar;

/**
 * Абстрактный базовый класс для коллекторов
 */
abstract class AbstractCollector implements CollectorInterface
{
    protected bool $enabled = true;
    protected int $priority = 100;
    protected array $data = [];

    /**
     * {@inheritDoc}
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * {@inheritDoc}
     */
    public function getBadge(): ?string
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaderStats(): array
    {
        return [];
    }

    /**
     * Установить приоритет
     */
    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Включить/выключить коллектор
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Получить собранные данные
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Форматировать байты в человекочитаемый формат
     * 
     * @param int $bytes Количество байтов
     * @return string Отформатированная строка
     */
    protected function formatBytes(int $bytes): string
    {
        return \Core\Utils\FormatHelper::formatBytes($bytes, 2);
    }

    /**
     * Форматировать время в человекочитаемый формат
     * 
     * Принимает время в миллисекундах и возвращает отформатированную строку
     * с автоматическим выбором единиц измерения (μs, ms, s).
     * 
     * @param float $timeMs Время в миллисекундах
     * @return string Отформатированная строка
     */
    protected function formatTime(float $timeMs): string
    {
        return \Core\Utils\FormatHelper::formatTime($timeMs, 2);
    }

    /**
     * Получить цвет по порогу
     * 
     * @param float $value Текущее значение
     * @param float $warning Порог предупреждения
     * @param float $critical Критический порог
     * @return string Hex-код цвета
     */
    protected function getColorByThreshold(float $value, float $warning, float $critical): string
    {
        return \Core\Utils\FormatHelper::getColorByThreshold($value, $warning, $critical);
    }
}
