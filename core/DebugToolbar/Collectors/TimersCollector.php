<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
use Core\DebugToolbar\ColorPalette;

/**
 * Коллектор таймеров
 */
class TimersCollector extends AbstractCollector
{
    public function __construct()
    {
        $this->priority = 70;
    }

    public function getName(): string
    {
        return 'timers';
    }

    public function getTitle(): string
    {
        return 'Timers';
    }

    public function getIcon(): string
    {
        return '⏱️';
    }

    public function isEnabled(): bool
    {
        return class_exists('\Core\DebugTimer');
    }

    public function collect(): void
    {
        // Собираем общее время выполнения
        $this->data = [
            'execution_time' => $this->getExecutionTime(),
        ];
    }

    public function render(): string
    {
        $html = '<div style="padding: 20px;">';
        $html .= '<h3 style="margin-top: 0;">⏱️ Execution Time</h3>';
        
        $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
        $html .= '<div><strong>Total Time:</strong> ' . $this->formatTime($this->data['execution_time']) . '</div>';
        $html .= '</div>';
        
        $html .= '<div style="margin-top: 20px; text-align: center; color: #757575;">Use timer_dump() to display custom timers</div>';
        $html .= '</div>';

        return $html;
    }

    public function getHeaderStats(): array
    {
        if (empty($this->data)) {
            return [];
        }

        $time = $this->data['execution_time'];
        $timeColor = $this->getTimeColor($time, 500, 1000);

        return [
            [
                'icon' => '⏱️',
                'value' => $this->formatTime($time),
                'color' => $timeColor,
            ]
        ];
    }

    private function getExecutionTime(): float
    {
        if (defined('VILNIUS_START')) {
            return (microtime(true) - VILNIUS_START) * 1000;
        }
        return 0;
    }
}
