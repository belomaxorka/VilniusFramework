<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
use Core\DebugToolbar\ColorPalette;
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
        return $this->countBadge('queries');
    }

    public function render(): string
    {
        if (empty($this->data['queries'])) {
            return $this->renderEmptyState('No queries executed');
        }

        $stats = $this->data['stats'] ?? [];
        $html = '<div style="padding: 10px;">';

        // Статистика
        if (!empty($stats)) {
            $html .= '<div style="background: #f5f5f5; padding: 10px; border-radius: 4px; margin-bottom: 10px; font-size: 12px;">';
            $html .= '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">';
            $html .= '<div><strong>Total:</strong> ' . $stats['total'] . ' queries</div>';
            $html .= '<div><strong>Time:</strong> ' . $this->formatTime($stats['total_time']) . '</div>';
            $html .= '<div><strong>Avg:</strong> ' . $this->formatTime($stats['avg_time']) . '</div>';

            if ($stats['slow'] > 0) {
                $html .= '<div style="color: #ef5350;"><strong>⚠ Slow:</strong> ' . $stats['slow'] . ' queries</div>';
            }
            if ($stats['duplicates'] > 0) {
                $html .= '<div style="color: #ff9800;"><strong>⚠ Duplicates:</strong> ' . $stats['duplicates'] . ' queries</div>';
            }
            $html .= '<div><strong>Rows:</strong> ' . $stats['total_rows'] . ' total</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        // Список запросов
        $html .= '<div style="max-height: 400px; overflow-y: auto;">';

        foreach ($this->data['queries'] as $index => $query) {
            $bgColor = $query['is_slow'] ? '#ffebee' : 'white';
            $borderColor = $query['is_slow'] ? '#ef5350' : '#e0e0e0';

            $html .= '<div style="background: ' . $bgColor . '; border: 1px solid ' . $borderColor . '; padding: 10px; margin-bottom: 8px; border-radius: 4px; font-size: 12px;">';

            // Заголовок
            $html .= '<div style="display: flex; justify-content: space-between; margin-bottom: 5px;">';
            $html .= '<strong>#' . ($index + 1) . '</strong>';
            $html .= '<div>';
            $html .= '<span style="color: ' . ($query['is_slow'] ? '#ef5350' : '#66bb6a') . '; margin-right: 10px;">' . $this->formatTime($query['time']) . '</span>';
            $html .= '<span style="color: #757575;">' . $query['rows'] . ' rows</span>';
            $html .= '</div>';
            $html .= '</div>';

            // SQL
            $html .= '<pre style="background: #f5f5f5; padding: 8px; border-radius: 3px; margin: 5px 0; overflow-x: auto; font-size: 11px;">';
            $html .= $this->highlightSql($query['sql']);
            $html .= '</pre>';

            // Bindings
            if (!empty($query['bindings'])) {
                $html .= '<div style="font-size: 11px; color: #757575; margin-top: 5px;">';
                $html .= '<strong>Bindings:</strong> <code>' . htmlspecialchars(json_encode($query['bindings'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '</code>';
                $html .= '</div>';
            }

            // Caller
            if (isset($query['caller']) && $query['caller']) {
                $html .= '<div style="font-size: 10px; color: #9e9e9e; margin-top: 3px;">';
                $html .= '📍 ' . htmlspecialchars($query['caller']['file']) . ':' . $query['caller']['line'];
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Подсветка SQL синтаксиса
     */
    private function highlightSql(string $sql): string
    {
        $keywords = ['SELECT', 'FROM', 'WHERE', 'JOIN', 'LEFT', 'RIGHT', 'INNER', 'ON', 'AND', 'OR', 'ORDER', 'BY', 'GROUP', 'HAVING', 'LIMIT', 'OFFSET', 'INSERT', 'INTO', 'VALUES', 'UPDATE', 'SET', 'DELETE', 'AS', 'DISTINCT', 'COUNT', 'SUM', 'AVG', 'MAX', 'MIN', 'NOT', 'NULL'];

        $highlighted = htmlspecialchars($sql);

        foreach ($keywords as $keyword) {
            $highlighted = preg_replace(
                '/\b(' . $keyword . ')\b/i',
                '<span style="color: #0066cc; font-weight: bold;">$1</span>',
                $highlighted
            );
        }

        return $highlighted;
    }

    public function getHeaderStats(): array
    {
        $count = count($this->data['queries'] ?? []);
        if ($count === 0) {
            return [];
        }

        $stats = $this->data['stats'] ?? [];
        $slowCount = $stats['slow'] ?? 0;
        $queryColor = $slowCount > 0 ? ColorPalette::ERROR : ColorPalette::SUCCESS;

        return [[
            'icon' => '🗄️',
            'value' => $count . ' queries' . ($slowCount > 0 ? ' (' . $slowCount . ' slow)' : ''),
            'color' => $queryColor,
        ]];
    }
}
