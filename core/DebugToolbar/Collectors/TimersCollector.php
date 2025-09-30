<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;

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
        // Пока просто заглушка
        // В будущем можно добавить метод в DebugTimer для получения всех таймеров
    }

    public function render(): string
    {
        $html = '<div style="padding: 20px;">';
        $html .= '<div style="text-align: center; color: #757575;">Use timer_dump() to display timers</div>';
        $html .= '</div>';

        return $html;
    }
}
