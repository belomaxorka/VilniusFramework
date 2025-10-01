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
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Форматировать время в миллисекундах
     */
    protected function formatTime(float $time): string
    {
        if ($time < 1) {
            return number_format($time * 1000, 2) . 'μs';
        }
        return number_format($time, 2) . 'ms';
    }

    /**
     * Получить цвет по порогу
     */
    protected function getColorByThreshold(float $value, float $warning, float $critical): string
    {
        if ($value >= $critical) {
            return '#ef5350'; // red
        }
        if ($value >= $warning) {
            return '#ffa726'; // orange
        }
        return '#66bb6a'; // green
    }
}
