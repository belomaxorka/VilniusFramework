<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
use Core\MemoryProfiler;

/**
 * Коллектор памяти
 */
class MemoryCollector extends AbstractCollector
{
    public function __construct()
    {
        $this->priority = 60;
    }

    public function getName(): string
    {
        return 'memory';
    }

    public function getTitle(): string
    {
        return 'Memory';
    }

    public function getIcon(): string
    {
        return '💾';
    }

    public function isEnabled(): bool
    {
        return class_exists('\Core\MemoryProfiler');
    }

    public function collect(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->data = [
            'current' => MemoryProfiler::current(),
            'peak' => MemoryProfiler::peak(),
            'limit' => $this->getMemoryLimit(),
        ];
    }

    public function render(): string
    {
        $html = '<div style="padding: 20px;">';
        $html .= '<h3 style="margin-top: 0;">💾 Memory Usage</h3>';

        $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
        $html .= '<div style="margin-bottom: 10px;"><strong>Current:</strong> ' . $this->formatBytes($this->data['current']) . '</div>';
        $html .= '<div style="margin-bottom: 10px;"><strong>Peak:</strong> ' . $this->formatBytes($this->data['peak']) . '</div>';

        if ($this->data['limit'] > 0) {
            $percent = ($this->data['peak'] / $this->data['limit']) * 100;
            $html .= '<div style="margin-bottom: 10px;"><strong>Limit:</strong> ' . $this->formatBytes($this->data['limit']) . '</div>';
            $html .= '<div><strong>Usage:</strong> ' . number_format($percent, 2) . '%</div>';

            // Progress bar
            $barColor = $this->getColorByThreshold($percent, 50, 75);
            $html .= '<div style="margin-top: 10px; background: #e0e0e0; border-radius: 10px; overflow: hidden; height: 20px;">';
            $html .= '<div style="background: ' . $barColor . '; width: ' . min(100, $percent) . '%; height: 100%;"></div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function getHeaderStats(): array
    {
        if (!$this->isEnabled() || empty($this->data)) {
            return [];
        }

        $memoryPercent = $this->getMemoryPercent();
        $memoryColor = $this->getColorByThreshold($memoryPercent, 50, 75);

        // Форматируем память с двумя знаками после запятой
        $peakMb = $this->data['peak'] / (1024 * 1024);
        $formattedMemory = number_format($peakMb, 2, '.', '') . ' MB';

        return [
            [
                'icon' => '💾',
                'value' => $formattedMemory,
                'color' => $memoryColor,
            ]
        ];
    }

    private function getMemoryPercent(): float
    {
        if ($this->data['limit'] === 0 || !$this->data['peak']) {
            return 0;
        }

        return ($this->data['peak'] / $this->data['limit']) * 100;
    }

    /**
     * Получить лимит памяти из php.ini
     * 
     * Использует MemoryProfiler для получения лимита памяти,
     * что обеспечивает единообразную обработку во всей системе.
     * 
     * @return int Лимит памяти в байтах
     */
    private function getMemoryLimit(): int
    {
        return MemoryProfiler::getMemoryLimit();
    }
}
