<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
use Core\DebugContext;

/**
 * Коллектор Debug Contexts
 */
class ContextsCollector extends AbstractCollector
{
    public function __construct()
    {
        $this->priority = 50;
    }

    public function getName(): string
    {
        return 'contexts';
    }

    public function getTitle(): string
    {
        return 'Contexts';
    }

    public function getIcon(): string
    {
        return '📁';
    }

    public function isEnabled(): bool
    {
        return class_exists('\Core\DebugContext');
    }

    public function collect(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->data['contexts'] = DebugContext::getAll();
    }

    public function getBadge(): ?string
    {
        return $this->countBadge('contexts');
    }

    public function render(): string
    {
        if (empty($this->data['contexts'])) {
            return $this->renderEmptyState('No contexts created');
        }

        $html = '<div style="padding: 10px; max-height: 400px; overflow-y: auto;">';

        foreach ($this->data['contexts'] as $name => $context) {
            $config = $context['config'];

            $html .= '<div style="background: white; border-left: 4px solid ' . $config['color'] . '; padding: 10px; margin-bottom: 8px; border-radius: 4px;">';
            $html .= '<div style="font-weight: bold; color: ' . $config['color'] . ';">' . $config['icon'] . ' ' . $config['label'] . '</div>';
            $html .= '<div style="font-size: 12px; color: #757575; margin-top: 5px;">Items: ' . count($context['items']) . '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    public function getHeaderStats(): array
    {
        $count = count($this->data['contexts'] ?? []);
        if ($count === 0) {
            return [];
        }

        return [[
            'icon' => '📁',
            'value' => $count . ' contexts',
            'color' => '#66bb6a',
        ]];
    }
}
