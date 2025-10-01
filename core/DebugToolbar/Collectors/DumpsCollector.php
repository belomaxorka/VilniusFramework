<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
use Core\Debug;

/**
 * Коллектор Debug Dumps
 */
class DumpsCollector extends AbstractCollector
{
    public function __construct()
    {
        $this->priority = 90;
    }

    public function getName(): string
    {
        return 'dumps';
    }

    public function getTitle(): string
    {
        return 'Dumps';
    }

    public function getIcon(): string
    {
        return '🔍';
    }

    public function isEnabled(): bool
    {
        return class_exists('\Core\Debug');
    }

    public function collect(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->data['dumps'] = Debug::getOutput(true);
    }

    public function getBadge(): ?string
    {
        $count = count($this->data['dumps'] ?? []);
        return $count > 0 ? (string)$count : null;
    }

    public function render(): string
    {
        if (empty($this->data['dumps'])) {
            return '<div style="padding: 20px; text-align: center; color: #757575;">No dumps collected</div>';
        }

        $html = '<div style="padding: 10px; max-height: 400px; overflow-y: auto;">';
        foreach ($this->data['dumps'] as $index => $dump) {
            $html .= '<div style="margin-bottom: 10px;">' . $dump['output'] . '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    public function getHeaderStats(): array
    {
        $count = count($this->data['dumps'] ?? []);
        if ($count === 0) {
            return [];
        }

        return [[
            'icon' => '🔍',
            'value' => $count . ' dumps',
            'color' => '#66bb6a',
        ]];
    }
}
