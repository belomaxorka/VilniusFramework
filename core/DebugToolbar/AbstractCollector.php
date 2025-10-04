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

    /**
     * Форматировать значение для отображения
     * 
     * Универсальный метод для преобразования любого типа данных
     * в читаемую строку для отображения в коллекторах.
     * 
     * @param mixed $value Значение для форматирования
     * @param bool $truncate Обрезать длинные строки
     * @param int $maxLength Максимальная длина строки (если truncate = true)
     * @return string Отформатированная строка
     */
    protected function formatValue(mixed $value, bool $truncate = true, int $maxLength = 50): string
    {
        if (is_string($value)) {
            if ($truncate && mb_strlen($value) > $maxLength) {
                return htmlspecialchars(mb_substr($value, 0, $maxLength)) . '...';
            }
            return htmlspecialchars($value);
        }
        
        if (is_array($value)) {
            if ($truncate) {
                return 'Array (' . count($value) . ' items)';
            }
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
        if (is_object($value)) {
            return 'Object (' . get_class($value) . ')';
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_null($value)) {
            return 'null';
        }
        
        return (string)$value;
    }

    /**
     * Получить цвет для HTTP метода
     * 
     * @param string $method HTTP метод (GET, POST, PUT, PATCH, DELETE, etc.)
     * @return string Hex-код цвета
     */
    protected function getMethodColor(string $method): string
    {
        return ColorPalette::getHttpMethodColor($method);
    }

    /**
     * Рендер пустого состояния
     * 
     * @param string $message Сообщение для отображения
     * @return string HTML
     */
    protected function renderEmptyState(string $message): string
    {
        return HtmlRenderer::renderEmptyState($message);
    }

    /**
     * Рендер badge для вкладки
     * 
     * Стандартная реализация badge на основе количества элементов.
     * 
     * @param string $dataKey Ключ в $this->data для подсчёта элементов
     * @return string|null Строка с количеством или null
     */
    protected function countBadge(string $dataKey): ?string
    {
        $count = count($this->data[$dataKey] ?? []);
        return $count > 0 ? (string)$count : null;
    }

    /**
     * Получить цвет для уровня лога/ошибки
     * 
     * @param string $level Уровень (debug, info, warning, error, critical)
     * @return string Hex-код цвета
     */
    protected function getLevelColor(string $level): string
    {
        return ColorPalette::getLogLevelColor($level);
    }

    /**
     * Получить цвет для времени выполнения
     * 
     * @param float $timeMs Время в миллисекундах
     * @param float $fast Порог быстрого выполнения (по умолчанию 100ms)
     * @param float $medium Порог среднего выполнения (по умолчанию 500ms)
     * @return string Hex-код цвета
     */
    protected function getTimeColor(float $timeMs, float $fast = 100, float $medium = 500): string
    {
        return ColorPalette::getTimeColor($timeMs, $fast, $medium);
    }
}
