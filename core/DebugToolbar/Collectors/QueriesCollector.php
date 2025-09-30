<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
use Core\QueryDebugger;

/**
 * Коллектор SQL запросов
 */
class QueriesCollector extends AbstractCollector
{
    public function __construct()
    {
        $this->priority = 80;
    }

    public function getName(): string
    {
        return 'queries';
    }

    public function getTitle(): string
    {
        return 'Queries';
    }

    public function getIcon(): string
    {
        return '🗄️';
    }

    public function isEnabled(): bool
    {
        return class_exists('\Core\QueryDebugger');
    }

    public function collect(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->data['queries'] = QueryDebugger::getQueries();
        $this->data['stats'] = QueryDebugger::getStats();
    }

    public function getBadge(): ?string
    {
        $count = count($this->data['queries'] ?? []);
        return $count > 0 ? (string)$count : null;
    }

    public function render(): string
    {
        if (empty($this->data['queries'])) {
            return '<div style="padding: 20px; text-align: center; color: #757575;">No queries executed</div>';
        }

        $html = '<div style="padding: 10px; max-height: 400px; overflow-y: auto;">';

        foreach ($this->data['queries'] as $index => $query) {
            $bgColor = $query['is_slow'] ? '#ffebee' : 'white';
            $borderColor = $query['is_slow'] ? '#ef5350' : '#e0e0e0';

            $html .= '<div style="background: ' . $bgColor . '; border: 1px solid ' . $borderColor . '; padding: 10px; margin-bottom: 8px; border-radius: 4px; font-size: 12px;">';

            $html .= '<div style="display: flex; justify-content: space-between; margin-bottom: 5px;">';
            $html .= '<strong>#' . ($index + 1) . '</strong>';
            $html .= '<span style="color: ' . ($query['is_slow'] ? '#ef5350' : '#66bb6a') . ';">' . $this->formatTime($query['time']) . ' | ' . $query['rows'] . ' rows</span>';
            $html .= '</div>';

            $html .= '<pre style="background: #f5f5f5; padding: 8px; border-radius: 3px; margin: 0; overflow-x: auto; font-size: 11px;">' . htmlspecialchars($query['sql']) . '</pre>';

            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    public function getHeaderStats(): array
    {
        $count = count($this->data['queries'] ?? []);
        if ($count === 0) {
            return [];
        }

        $stats = $this->data['stats'] ?? [];
        $slowCount = $stats['slow'] ?? 0;
        $queryColor = $slowCount > 0 ? '#ef5350' : '#66bb6a';

        return [[
            'icon' => '🗄️',
            'value' => $count . ' queries' . ($slowCount > 0 ? ' (' . $slowCount . ' slow)' : ''),
            'color' => $queryColor,
        ]];
    }
}
